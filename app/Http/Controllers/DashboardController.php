<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\CardsInCollection;
use App\Models\CardsInDeck;
use App\Models\CardMetadata;
use App\Models\CollectedCardsFromSets;
use App\Models\DeckManagement;
use App\Models\MtgArchetype;
use App\Models\MtgBulkPrices;
use App\Models\MtgImageLookup;

class DashboardController extends Controller
{
    public function imageCoverage()
    {
        $results = DB::table('card_data_from_set_data')
            ->select(
                'set_name',
                DB::raw('COUNT(*) AS total'),
                DB::raw('SUM(image_url IS NOT NULL) AS with_image'),
                DB::raw('SUM(image_url IS NULL) AS missing')
            )
            ->groupBy('set_name')
            ->orderByDesc(DB::raw('SUM(image_url IS NULL)'))
            ->get();

        // Add coverage percentage
        $data = $results->map(function ($row) {
            $coverage = $row->total > 0 ? round(($row->with_image / $row->total) * 100, 2) : 0;
            return [
                'set_name' => $row->set_name,
                'total' => $row->total,
                'with_image' => $row->with_image,
                'missing' => $row->missing,
                'coverage_percent' => $coverage
            ];
        });

        return response()->json($data);
    }

    public function dataCoverage()
    {
        $setCode = request()->route('set_name');

        $query = DB::table('card_data_from_set_data as raw')
            ->select([
                'raw.set_name',
                'msd.release_date',
                DB::raw('COUNT(raw.id) AS total_raw_cards'),
                DB::raw('COUNT(DISTINCT meta.id) AS metadata_linked'),
                DB::raw('COUNT(DISTINCT norm.id) AS normalized_cards'),
                DB::raw('COUNT(DISTINCT img.id) AS image_hydrated'),

                DB::raw('ROUND(COUNT(DISTINCT meta.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS metadata_pct'),
                DB::raw('ROUND(COUNT(DISTINCT norm.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS normalization_pct'),
                DB::raw('ROUND(COUNT(DISTINCT img.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS image_pct'),

                DB::raw('SUM(CASE WHEN raw.set_name IS NULL THEN 1 ELSE 0 END) AS missing_set_name_count'),
                DB::raw('SUM(CASE WHEN meta.last_enriched_at IS NULL THEN 1 ELSE 0 END) AS missing_enrichment_timestamp'),
                DB::raw('MIN(meta.last_enriched_at) AS earliest_enrichment'),
                DB::raw('MAX(meta.last_enriched_at) AS latest_enrichment'),
                DB::raw('COUNT(DISTINCT meta.logic_version) AS logic_versions_used'),
                DB::raw('SUM(CASE WHEN img.hydrated_via_command = 1 THEN 1 ELSE 0 END) AS image_hydrated_via_command'),
            ])
            ->leftJoin('card_metadata as meta', 'meta.card_data_from_set_data_id', '=', 'raw.id')
            ->leftJoin('card_data_normalized as norm', function ($join) {
                $join->on('norm.normalized_name', '=', 'meta.normalized_name')
                    ->on('norm.set_code', '=', 'raw.set_name');
            })
            ->leftJoin('mtg_image_lookups as img', 'img.card_uuid', '=', 'raw.card_uuid')
            ->leftJoin('magic_set_data as msd', 'msd.set_name', '=', 'raw.set_name')
            ->whereNotNull('raw.set_name');

        if ($setCode) {
            $query->where('raw.set_name', $setCode);
        }

        $coverageStats = $query
            ->groupBy('raw.set_name', 'msd.release_date')
            ->havingRaw('COUNT(raw.id) >= 78')
            ->orderBy('msd.release_date', 'asc')
            ->get();

        return response()->json($coverageStats);
    }

    public function cardMetadataBySlugAndNumber($set, $cardSlug, $numberInSet) {
        // Load the specific printing
        $card = CardDataFromSetData::with([
            'setData',
            'cardMetadata',
            'collectedCards'
        ])->where('slug', $cardSlug)
        ->where('number_in_set', $numberInSet)
        ->where('set_name', $set)
        ->firstOrFail();

        return $this->cardMetadataByCard($card);
    }

    public function cardMetadata($cardSlug)
    {
        // Load the specific printing
        $card = CardDataFromSetData::with([
            'setData',
            'cardMetadata',
            'collectedCards'
        ])->where('slug', $cardSlug)->firstOrFail();

        return $this->cardMetadataByCard($card);        
    }

    public function cardMetadataByCard($card) {
        $cardId = $card->id;
        $metadata = $card->cardMetadata;

        // Try to find the normalized record for this printing
        $normalized = CardDataNormalized::where('source_printing_id', $cardId)->first();

        // If not found, fallback to matching by name
        if (!$normalized) {
            $fallbackName = $card->name;
            $normalized = CardDataNormalized::whereRaw('LOWER(normalized_name) = ?', [mb_strtolower($fallbackName)])->first();
        }

        $sourceCard = $normalized?->sourceCard;

        // Attach back image if available
        $backImageUrl = MtgImageLookup::where('card_uuid', $metadata->scryfallId)->value('canonical_image_url_back');
        if ($backImageUrl) {
            $card->back_image_url = $backImageUrl;
        }

        // Normalize name for consistent matching
        $normalizedName = mb_strtolower($normalized->normalized_name);

        // Get all printings of this card by normalized name
        $relatedPrintings = CardDataFromSetData::whereRaw('LOWER(name) = ?', [$normalizedName])->get();

        // Load metadata rows keyed by printing ID
        $metadataRows = CardMetadata::whereIn('card_data_from_set_data_id', $relatedPrintings->pluck('id'))
            ->get()
            ->keyBy('card_data_from_set_data_id');

        // Get latest price per scryfall_id
        $prices = MtgBulkPrices::whereIn('scryfall_id', $metadataRows->pluck('scryfall_id')->filter()->unique()->values())
            ->orderByDesc('price_date')
            ->get()
            ->groupBy('scryfall_id')
            ->map(fn($group) => $group->first()->usd);

        // Build canonical map keyed by printing ID
        $bulkPriceData = [];

        foreach ($relatedPrintings as $print) {
            $meta = $metadataRows->get($print->id);
            $scryId = $meta->scryfall_id ?? null;
            $price = $prices[$scryId] ?? null;
            if ($price === null) continue;

            $purchaseUrls = [];
            if ($meta) {
                $raw = $meta->purchaseUrls;
                if (is_array($raw)) {
                    $purchaseUrls = $raw;
                } elseif (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $purchaseUrls = is_array($decoded) ? $decoded : [];
                }
            }

            $label = sprintf('%s #%s', $print->set_name, $print->number_in_set ?? $print->id);

            $bulkPriceData[$print->id] = [
                'id' => $print->id,
                'set_name' => $print->set_name,
                'number_in_set' => $print->number_in_set,
                'label' => $label,
                'price' => (float)$price,
                'purchaseUrls' => $purchaseUrls
            ];
        }

        // Total owned across all printings
        $totalOwned = CollectedCardsFromSets::whereIn('card_data_id', $relatedPrintings->pluck('id'))
                                              ->inActiveInventory()
                                              ->sum('card_count');

        // Deck usage across all normalized versions
        $normalizedIds = CardDataNormalized::whereIn('source_printing_id', $relatedPrintings->pluck('id'))->pluck('id');

        $cardName = $card->name;
        $normalizedCard = CardDataNormalized::whereRaw('LOWER(normalized_name) = ?', [mb_strtolower($cardName)])->first();

        $deckUsageSummary = collect();

        if ($normalizedCard) {
            $deckUsages = CardsInDeck::where('card_data_normalized_id', $normalizedCard->id)
                ->with('deckManagedBy.archetypeModel')
                ->get();

            $deckUsageSummary = $deckUsages->map(function ($entry) {
                $deck = $entry->deckManagedBy;
                if (!$deck) return null;

                $archetypeName = $deck->archetypeModel->name ?? $deck->archetype ?? 'Unknown';

                return [
                    'deck_id' => $deck->id,
                    'deck_name' => $deck->deck_name,
                    'format' => $deck->format,
                    'archetype' => $archetypeName,
                    'card_count' => $entry->card_count,
                    'external_link' => $deck->external_link,
                ];
            })->filter()->groupBy('archetype');
        }

        return response()->json([
            'card' => $card,
            'normalized' => $normalized,
            'metadata' => $metadata,
            'bulk_price_data' => $bulkPriceData,
            'total_owned' => $totalOwned,
            'deck_usages' => $deckUsageSummary,
            'related_printings' => $sourceCard->printings ?? []
        ]);
    }
}
