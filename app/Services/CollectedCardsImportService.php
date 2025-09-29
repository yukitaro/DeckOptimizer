<?php

namespace App\Services;

use App\Services\CollectedCardService;

use App\Models\CardDataFromSetData;
use App\Models\SetData;
use App\Models\SetsInCollection;

use App\Services\VariantCardResolver;

class CollectedCardsImportService 
{
    protected static array $knownFormats = [
        'manabox' => [
            'card_name' => 'Name',
            'set_code' => 'Set code',
            'card_count' => 'Quantity',
            'condition' => 'Condition',
            'is_foil' => 'Foil',
            'purchase_price' => 'Purchase price',
            'printing_variant' => null,
            'storage_location' => null,
        ],
        'deckbox' => [
            'card_name' => 'Card Name',
            'set_code' => 'Edition',
            'card_count' => 'Count',
            'condition' => 'Condition',
            'is_foil' => 'Foil',
            'purchase_price' => 'Price',
            'printing_variant' => 'Variant',
            'storage_location' => 'Location',
        ],
        'mtgcollectionbuilder' => [
            'card_name' => 'Name',
            'set_code' => 'Set',
            'card_count' => 'Qty',
            'condition' => null,
            'is_foil' => 'Foil',
            'purchase_price' => 'Avg Price',
            'number_in_set' => 'Number',
            'printing_variant' => null,
            'storage_location' => null,
            
        ]
        // Add more formats here
    ];

    protected static function detectFormat(array $headerRow): ?array
    {
        foreach (static::$knownFormats as $format => $map) {
            $matched = collect($map)->filter(fn($header) => $header && in_array($header, $headerRow));
            if ($matched->count() >= 3) { // threshold for confidence
                return $map;
            }
        }

        return null;
    }

    public static function importToCollection(string $mode, array $records, int $collectionId): void 
    {
        // Step 1: Validate input
        if (empty($records)) {
            throw new \Exception('No records provided for import');
        }
        
        \Log::info('=== IMPORT START ===', [
            'mode' => $mode, 
            'collectionId' => $collectionId, 
            'recordCount' => count($records),
            'firstRecordKeys' => array_keys($records[0])
        ]);

        if ($mode === 'set') {
            \Log::info("Resetting collection before import", ['collectionId' => $collectionId]);

            $setIds = SetsInCollection::where('collection_id', $collectionId)->pluck('id');

            if ($setIds->isNotEmpty()) {
                \DB::table('collected_cards_from_sets')->whereIn('sets_in_collection_id', $setIds)->delete();
                SetsInCollection::whereIn('id', $setIds)->delete();
            }

            \Log::info("Collection reset complete", ['deletedSetCount' => $setIds->count()]);
        }        

        // Step 2: Detect format
        $headerMap = static::detectFormat(array_keys($records[0]));
        if (!$headerMap) {
            \Log::error('Format detection failed', ['headers' => array_keys($records[0])]);
            throw new \Exception('Could not detect CSV format');
        }
        \Log::info('Format detected', $headerMap);

        // Step 3: Group by set codes
        $setCodeColumn = $headerMap['set_code'];
        $grouped = collect($records)->groupBy($setCodeColumn);
        \Log::info('Records grouped by set', [
            'setCodeColumn' => $setCodeColumn,
            'setCodes' => $grouped->keys()->toArray(),
            'groupCounts' => $grouped->map->count()->toArray()
        ]);

        // Step 4: Look up set IDs
        $normalizedSetNames = $grouped->keys()->map(fn($name) => self::normalizeSetName($name))->unique();

        $setMap = SetData::whereIn('official_set_code', $normalizedSetNames)
            ->pluck('id', 'official_set_code');
        \Log::info('Set lookup results', [
            'requestedSets' => $grouped->keys()->toArray(),
            'foundSets' => $setMap->toArray(),
            'missingSets' => $grouped->keys()->diff($setMap->keys())->toArray()
        ]);

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($grouped as $setCode => $recordsForSet) {
            $normalizedSetName = self::normalizeSetName($setCode);
            $setId = $setMap[$normalizedSetName] ?? null;

            if (!$setId) {
                $skippedCount += count($recordsForSet);
                \Log::warning("Skipping unknown set: {$setCode}");
                continue;
            }

            \Log::info("Processing set: {$setCode}", ['setId' => $setId, 'recordCount' => count($recordsForSet)]);

            $setInCollectionId = static::resolveOrCreateSetInCollection($collectionId, $setId);

            foreach ($recordsForSet as $record) {
                $cardName = $record[$headerMap['card_name']];
                $normalizedSetName = self::normalizeSetName($setCode);
                $dbSetCode = SetData::where('official_set_code', $normalizedSetName)->value('set_name');
                $cardData = VariantCardResolver::resolveCardData($cardName, $dbSetCode ?: $setCode);
                $cardDataId = $cardData?->id;

/* 
                $cardName = $record[$headerMap['card_name']];
                $cardDataId = $cardMap[$cardName] ?? null;
 */                
                if (!$cardDataId) {
                    \Log::debug("Skipping unknown card: {$cardName}");
                    continue;
                }

                $cardCount = (int)($record[$headerMap['card_count']] ?? 0);
                if ($cardCount <= 0) {
                    \Log::debug("Skipping card with zero count: {$cardName}");
                    continue;
                }

                try {
                    CollectedCardService::handle($mode, [
                        'set_in_collection_id' => $setInCollectionId,
                        'card_data_id' => $cardDataId,
                        'card_count' => $cardCount,
                        'condition' => ($headerMap['condition'] ? $record[$headerMap['condition']] : null) ?? null,
                        'is_foil' => strtolower($record[$headerMap['is_foil']] ?? '') === 'yes',
                        'printing_variant' => ($headerMap['printing_variant'] ? $record[$headerMap['printing_variant']] : null) ?? null,
                        'purchase_price' => ($headerMap['purchase_price'] ? $record[$headerMap['purchase_price']] : ($headerMap['average_price'] ? $record[$headerMap['average_price']] : null)) ?? null,
                        'storage_location' => ($headerMap['storage_location'] ? $record[$headerMap['storage_location']] : null) ?? null
                    ]);
                    $processedCount++;
                    \Log::debug("Successfully processed: {$cardName}");
                } catch (\Exception $e) {
                    \Log::error("Failed to process card: {$cardName}", ['error' => $e->getMessage()]);
                }
            }
        }

        \Log::info('=== IMPORT COMPLETE ===', [
            'processedCount' => $processedCount,
            'skippedCount' => $skippedCount,
            'totalRecords' => count($records)
        ]);
    }

    protected function merge(array $records): void 
    {
        foreach ($records as $record) {

        }
    }

    protected static function resolveOrCreateSetInCollection(int $collectionId, int $setId): int
    {
        $setInCollection = SetsInCollection::firstOrCreate([
            'collection_id' => $collectionId,
            'set_id' => $setId,
        ]);

        return $setInCollection->id;
    }

    public static function normalizeSetName(string $raw): string
    {
        return trim(preg_replace('/\s+(Variants|Extras|Alternate)$/i', '', $raw));
    }
}