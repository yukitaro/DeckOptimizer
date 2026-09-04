<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\CollectedCardsFromSets;
use App\Models\CardDataFromSetData;

class CollectionCardQueryBuilder
{
    protected Builder $query;

    public function getQuery(): Builder
    {
        return $this->query;
    }

    public function __construct(Request $request, array $setIds)
    {
        $this->query = CollectedCardsFromSets::query()
            ->whereIn('set_in_collection_id', $setIds)
            ->with([
                'cardFromSet',
                'cardFromSet.cardMetadata',
                'cardFromSet.cardMetadata.prices'
            ]);

        $this->applySearch($request);
        $this->applyFilters($request);
        $this->applySorting($request);
    }

    protected function applySearch(Request $request): void
    {
        $search = trim($request->input('filters.search', ''));
        if ($search !== '') {
            $matchingCardIds = CardDataFromSetData::query()
                ->where('name', 'like', '%' . $search . '%')
                ->pluck('id')
                ->toArray();

            $this->query->whereIn('card_data_id', $matchingCardIds ?: [-1]); // force empty if none
        }
    }

    protected function applyFilters(Request $request): void
    {
        $filters = $request->input('filters', []);

        if (!empty($filters['sets'])) {
            $setCodes = $this->parseFilterArray($filters['sets']);
            $this->query->whereHas('cardFromSet', fn($q) => $q->whereIn('set_name', $setCodes));
        }

        if (!empty($filters['colors'])) {
            $colors = array_map('strtoupper', $this->parseFilterArray($filters['colors']));

            $this->query->whereHas('cardFromSet', function ($q) use ($colors) {
                $q->where(function ($inner) use ($colors) {
                    foreach ($colors as $color) {
                        // Strips spaces and checks comma-delimited position cleanly
                        $inner->orWhereRaw("FIND_IN_SET(?, REPLACE(colors, ' ', '')) > 0", [$color]);
                    }
                });
            });
        }

        if (!empty($filters['rarities'])) {
            $rarities = $this->parseFilterArray($filters['rarities']);
            $this->query->whereHas('cardFromSet', fn($q) => $q->whereIn('rarity', $rarities));
        }

        if (!empty($filters['date_start']) || !empty($filters['date_end'])) {
            $start = $filters['date_start'] ?? null;
            $end = $filters['date_end'] ?? null;

            $this->query->whereHas('cardFromSet', function ($q) use ($start, $end) {
                if ($start) {
                    $q->whereRaw("STR_TO_DATE(release_date, '%Y-%m-%d') >= ?", [$start]);
                }
                if ($end) {
                    $q->whereRaw("STR_TO_DATE(release_date, '%Y-%m-%d') <= ?", [$end]);
                }
            });
        }

        $excludeMulti = $this->parseBoolean($filters['excludeMultiColor'] ?? false);

        if ($excludeMulti) {
            // Keep only single-color cards when requested.
            $this->query->whereHas('cardFromSet', function ($q) {
                $q->whereNotNull('colors')
                    ->where('colors', '!=', '')
                    ->whereRaw("INSTR(REPLACE(colors, ' ', ''), ',') = 0");
            });
        }        
    }

    protected function applySorting(Request $request): void
    {
        [$sort, $direction] = $this->normalizeSort($request);

        switch ($sort) {
            case 'price':
                $this->query
                    ->join('card_data_from_set_data as cfs_price', 'collected_cards.card_data_id', '=', 'cfs_price.id')
                    ->join('card_metadata as cm_price', 'cfs_price.card_metadata_id', '=', 'cm_price.id')
                    ->leftJoin('mtg_bulk_prices as prices', 'cm_price.scryfallId', '=', 'prices.scryfall_id')
                    ->select('collected_cards.*')
                    ->orderByRaw("
                        COALESCE(
                            CASE 
                                WHEN collected_cards.is_foil = 1 THEN prices.usd_foil 
                                ELSE prices.usd 
                            END, 
                            0
                        ) {$direction}
                    ")
                    ->orderBy('collected_cards.id');
                break;

            case 'name':
                $this->query
                    ->join('card_data_from_set_data as cfs', 'collected_cards.card_data_id', '=', 'cfs.id')
                    ->select('collected_cards.*')
                    ->orderBy('cfs.name', $direction)
                    ->orderBy('collected_cards.id');
                break;

            case 'count':
                $this->query
                    ->orderBy('card_count', $direction)
                    ->orderBy('card_data_id');
                break;

            case 'set':
                $this->query
                    ->join('card_data_from_set_data as cfs_set', 'collected_cards.card_data_id', '=', 'cfs_set.id')
                    ->select('collected_cards.*')
                    ->orderBy('cfs_set.set_code', $direction)
                    ->orderBy('cfs_set.name')
                    ->orderBy('collected_cards.id');
                break;

            default:
                $this->query
                    ->orderBy('card_count', 'desc')
                    ->orderBy('card_data_id');
        }
    }

    /**
     * Calculates total valuation and item counts for the FILTERED query.
     */
    public function getFilteredMetrics(): object
    {
        $query = (clone $this->query);

        // 1. Strip orders and explicit eager loads to optimize performance
        $query->reorder()->withOnly([]);

        // 2. Convert to Base Query Builder and reset any previously set SELECT columns
        $baseQuery = $query->toBase();
        $baseQuery->columns = null;

        return $baseQuery
            ->join('card_data_from_set_data as cds_agg', 'collected_cards.card_data_id', '=', 'cds_agg.id')
            ->join('card_metadata as meta_agg', 'cds_agg.card_metadata_id', '=', 'meta_agg.id')
            ->leftJoin('mtg_bulk_prices as prices_agg', 'meta_agg.scryfallId', '=', 'prices_agg.scryfall_id')
            ->selectRaw('
                COALESCE(SUM(collected_cards.card_count), 0) as total_card_count,
                COALESCE(SUM(
                    collected_cards.card_count * COALESCE(
                        CASE 
                            WHEN collected_cards.is_foil = 1 THEN prices_agg.usd_foil 
                            ELSE prices_agg.usd 
                        END, 
                        0
                    )
                ), 0) as total_market_value
            ')
            ->first() ?? (object) ['total_card_count' => 0, 'total_market_value' => 0];
    }

    /**
     * Calculates total valuation and item counts for the ENTIRE collection (Unfiltered).
     */
    public static function getGlobalMetrics(array $setIds): object
    {
        if (empty($setIds)) {
            return (object) ['total_card_count' => 0, 'total_market_value' => 0];
        }

        return CollectedCardsFromSets::query()
            ->whereIn('set_in_collection_id', $setIds)
            ->toBase()
            ->join('card_data_from_set_data as cds_agg', 'collected_cards.card_data_id', '=', 'cds_agg.id')
            ->join('card_metadata as meta_agg', 'cds_agg.card_metadata_id', '=', 'meta_agg.id')
            ->leftJoin('mtg_bulk_prices as prices_agg', 'meta_agg.scryfallId', '=', 'prices_agg.scryfall_id')
            ->selectRaw('
                COALESCE(SUM(collected_cards.card_count), 0) as total_card_count,
                COALESCE(SUM(
                    collected_cards.card_count * COALESCE(
                        CASE 
                            WHEN collected_cards.is_foil = 1 THEN prices_agg.usd_foil 
                            ELSE prices_agg.usd 
                        END, 
                        0
                    )
                ), 0) as total_market_value
            ')
            ->first() ?? (object) ['total_card_count' => 0, 'total_market_value' => 0];
    }

    protected function normalizeSort(Request $request): array {
        $validKeys = ['price', 'name', 'count', 'set', 'rarity'];

        $key = $request->input('sort.key');
        $direction = strtolower($request->input('sort.direction'));

        $key = in_array($key, $validKeys) ? $key : 'count';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'desc';

        return [$key, $direction];
    }

    protected function parseFilterArray(mixed $input): array
    {
        if (is_array($input)) {
            return array_filter($input);
        }

        if (is_string($input) && strlen(trim($input)) > 0) {
            return array_filter(explode(',', $input));
        }

        return [];
    }

    protected function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}