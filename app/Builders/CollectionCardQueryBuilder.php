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
            ->with('cardFromSet');

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
            $setCodes = array_filter(explode(',', $filters['sets']));
            $this->query->whereHas('cardFromSet', fn($q) => $q->whereIn('set_code', $setCodes));
        }

        if (!empty($filters['colors'])) {
            $colors = array_filter(explode(',', $filters['colors']));
            $this->query->whereHas('cardFromSet', function ($q) use ($colors) {
                $q->where(function ($inner) use ($colors) {
                    foreach ($colors as $color) {
                        $inner->orWhereJsonContains('colors', $color);
                    }
                });
            });
        }

        if (!empty($filters['rarities'])) {
            $rarities = array_filter(explode(',', $filters['rarities']));
            $this->query->whereHas('cardFromSet', fn($q) => $q->whereIn('rarity', $rarities));
        }
    }

    protected function applySorting(Request $request): void
    {
        [$sort, $direction] = $this->normalizeSort($request);

        switch ($sort) {
            case 'name':
                // Join for ordering by related name field
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
                    ->join('card_data_from_set_data as cfs_set', 'collected_cards_from_sets.card_data_id', '=', 'cfs_set.id')
                    ->select('collected_cards_from_sets.*')
                    ->orderBy('cfs_set.set_code', $direction)
                    ->orderBy('cfs_set.name')
                    ->orderBy('collected_cards_from_sets.id');
                break;

            default:
                // Fallback deterministic order
                $this->query
                    ->orderBy('card_count', 'desc')
                    ->orderBy('card_data_id');
        }
    }

    protected function normalizeSort(Request $request): array {
        $validKeys = ['name', 'count', 'set', 'rarity'];

        $key = $request->input('sort.key');
        $direction = strtolower($request->input('sort.direction'));

        $key = in_array($key, $validKeys) ? $key : 'count';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'desc';

        return [$key, $direction];
    }
}