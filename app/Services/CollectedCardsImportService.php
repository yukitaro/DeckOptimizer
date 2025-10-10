<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

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
    \Log::info('Import trigger', [
        'collectionId' => $collectionId,
        'recordCount' => count($records),
        'mode' => $mode
    ]);

    if (empty($records)) {
        throw new \Exception('No records provided for import');
    }

    $start = microtime(true);

    if ($mode === 'set') {
        $setIds = SetsInCollection::where('collection_management_id', $collectionId)->pluck('id');
        if ($setIds->isNotEmpty()) {
            \DB::table('collected_cards_from_sets')->whereIn('sets_in_collection_id', $setIds)->delete();
            SetsInCollection::whereIn('id', $setIds)->delete();
        }
    }

    $headerMap = static::detectFormat(array_keys($records[0]));
    if (empty($headerMap['set_code'])) {
        \Log::error('Missing set_code mapping from detected CSV format', [
            'headerMap' => $headerMap,
            'headers' => array_keys($records[0])
        ]);
        throw new \Exception('CSV missing required set_code column mapping');
    }

    $setCodeColumn = $headerMap['set_code'];
    $grouped = collect($records)->groupBy($setCodeColumn);

    $normalize = fn($code) => strtoupper(trim(self::normalizeSetName((string)$code)));
    $normalizedSetNames = $grouped->keys()->map($normalize)->unique()->values();

    $setMapByCode = SetData::whereIn('official_set_code', $normalizedSetNames)->pluck('id', 'official_set_code');
    $setMapByName = SetData::whereIn('set_name', $normalizedSetNames)->pluck('id', 'set_name');

    $setMap = $setMapByCode->merge($setMapByName)
        ->mapWithKeys(fn($id, $key) => [$normalize($key) => $id]);

    \Log::info('Set map built', [
        'normalized_count' => $normalizedSetNames->count(),
        'setMap_count' => $setMap->count(),
        'setMap_keys_sample' => $setMap->keys()->take(10)->toArray()
    ]);

    $processedCount = 0;
    $skippedCount = 0;

    foreach ($grouped as $setCode => $recordsForSet) {
        $normalizedSetName = $normalize($setCode);
        $setId = $setMap[$normalizedSetName] ?? null;

        if (! $setId) {
            \Log::warning('Unresolved set code', [
                'raw' => $setCode,
                'normalized' => $normalizedSetName,
                'rows_skipped' => count($recordsForSet)
            ]);

            $fallback = SetData::whereRaw('BINARY set_name = ?', [$setCode])
                ->orWhereRaw('BINARY official_set_code = ?', [$setCode])
                ->first(['id','set_name','official_set_code']);

            \Log::warning('Fallback probe', [
                'raw' => $setCode,
                'fallback' => optional($fallback)->toArray()
            ]);

            $skippedCount += count($recordsForSet);
            continue;
        }

        $setInCollectionId = static::resolveOrCreateSetInCollection($collectionId, $setId);
        $dbSetCode = SetData::where('id', $setId)->value('set_name') ?: SetData::where('id', $setId)->value('official_set_code');

        $cardCache = [];
        $perSetProcessed = 0;
        $perSetSkipped = 0;

        foreach ($recordsForSet as $idx => $record) {
            $cardName = $record[$headerMap['card_name']] ?? null;
            $rawCount = $record[$headerMap['card_count']] ?? null;
            $cardCount = (int)($rawCount ?? 0);

            if (empty($cardName) || $cardCount <= 0) {
                \Log::warning('Skipping row', [
                    'reason' => empty($cardName) ? 'missing card name' : 'non-positive count',
                    'set' => $normalizedSetName,
                    'row_index' => $idx,
                    'row' => $record
                ]);
                $skippedCount++; $perSetSkipped++;
                continue;
            }

            $cacheKey = $cardName . '|' . $dbSetCode;
            $cardData = $cardCache[$cacheKey] ??= VariantCardResolver::resolveCardData($cardName, $dbSetCode);

            if (! $cardData?->id) {
                \Log::warning('Card resolution failed', [
                    'card' => $cardName,
                    'set' => $dbSetCode,
                    'cache_key' => $cacheKey
                ]);
                $skippedCount++; $perSetSkipped++;
                continue;
            }

            $payload = [
                'set_in_collection_id' => $setInCollectionId,
                'card_data_id' => $cardData->id,
                'card_count' => $cardCount,
                'condition' => $headerMap['condition'] ? ($record[$headerMap['condition']] ?? null) : null,
                'is_foil' => strtolower($record[$headerMap['is_foil']] ?? '') === 'yes',
                'printing_variant' => $headerMap['printing_variant'] ? ($record[$headerMap['printing_variant']] ?? null) : null,
                'purchase_price' => $headerMap['purchase_price'] ? ($record[$headerMap['purchase_price']] ?? null) : null,
                'storage_location' => $headerMap['storage_location'] ? ($record[$headerMap['storage_location']] ?? null) : null
            ];

            try {
                CollectedCardService::handle($mode, $payload);
                $processedCount++; $perSetProcessed++;
            } catch (\Exception $e) {
                \Log::error('Insert failed', [
                    'card' => $cardName,
                    'error' => $e->getMessage()
                ]);
                $skippedCount++; $perSetSkipped++;
            }
        }

        \Log::info('Set import summary', [
            'set_code' => $setCode,
            'normalized' => $normalizedSetName,
            'set_id' => $setId,
            'sets_in_collection_id' => $setInCollectionId,
            'processed_in_set' => $perSetProcessed,
            'skipped_in_set' => $perSetSkipped,
            'rows_for_set' => count($recordsForSet)
        ]);
    }

    $duration = round(microtime(true) - $start, 2);
    \Log::info('Import complete', [
        'collectionId' => $collectionId,
        'processed' => $processedCount,
        'skipped' => $skippedCount,
        'duration_seconds' => $duration
    ]);

    }

    protected static function merge(array $records, ?array $headerMap = null): array
    {
        // helper to read a header value safely
        $get = function(array $row, ?string $key) {
            if ($key === null) {
                return null;
            }
            return $row[$key] ?? null;
        };

        $merged = [];

        foreach ($records as $row) {
            // Determine set code field name heuristically if headerMap provided
            $setKey = $headerMap['set_code'] ?? null;
            $cardNameKey = $headerMap['card_name'] ?? null;
            $countKey = $headerMap['card_count'] ?? null;
            $isFoilKey = $headerMap['is_foil'] ?? null;
            $conditionKey = $headerMap['condition'] ?? null;
            $purchaseKey = $headerMap['purchase_price'] ?? null;
            $storageKey = $headerMap['storage_location'] ?? null;

            $rawSet = $get($row, $setKey) ?? '';
            $rawCard = $get($row, $cardNameKey) ?? '';
            $rawCount = (int)($get($row, $countKey) ?? 0);

            $normalizedSet = self::normalizeSetName((string)$rawSet);
            $normalizedCard = trim((string)$rawCard);

            if ($normalizedCard === '' || $normalizedSet === '') {
                // skip malformed rows
                continue;
            }

            $groupKey = $normalizedSet . '|' . $normalizedCard;

            $isFoil = strtolower((string)($get($row, $isFoilKey) ?? '')) === 'yes';
            $condition = $get($row, $conditionKey) ?? null;
            $purchase = $get($row, $purchaseKey);
            $purchase = is_numeric($purchase) ? (float)$purchase : null;
            $storage = $get($row, $storageKey) ?? null;

            if (!isset($merged[$groupKey])) {
                $merged[$groupKey] = [
                    'set_code' => $normalizedSet,
                    'card_name' => $normalizedCard,
                    'card_count' => $rawCount,
                    'is_foil' => $isFoil,
                    'condition' => $condition,
                    'purchase_price' => $purchase,
                    'storage_location' => $storage,
                    'source_rows' => 1
                ];
            } else {
                // sum counts
                $merged[$groupKey]['card_count'] += $rawCount;
                // foil if any row is foil
                $merged[$groupKey]['is_foil'] = $merged[$groupKey]['is_foil'] || $isFoil;
                // prefer first non-empty condition
                if (empty($merged[$groupKey]['condition']) && !empty($condition)) {
                    $merged[$groupKey]['condition'] = $condition;
                }
                // keep highest purchase price seen
                if ($purchase !== null) {
                    $existing = $merged[$groupKey]['purchase_price'];
                    if ($existing === null || $purchase > $existing) {
                        $merged[$groupKey]['purchase_price'] = $purchase;
                    }
                }
                // prefer first non-empty storage
                if (empty($merged[$groupKey]['storage_location']) && !empty($storage)) {
                    $merged[$groupKey]['storage_location'] = $storage;
                }
                $merged[$groupKey]['source_rows']++;
            }
        }

        // Convert merged map back to array of rows shaped like original CSV keys where possible.
        $out = [];
        foreach ($merged as $item) {
            $out[] = [
                // keys chosen to match expected headerMap usages later
                'Set code' => $item['set_code'],            // aligns with common header 'Set code'
                'Name' => $item['card_name'],               // aligns with common header 'Name'
                'Quantity' => $item['card_count'],          // aligns with common header 'Quantity'
                'Foil' => $item['is_foil'] ? 'yes' : 'no',
                'Condition' => $item['condition'],
                'Purchase price' => $item['purchase_price'],
                'Location' => $item['storage_location'],
                '_merged_source_rows' => $item['source_rows']
            ];
        }

        return $out;
    }

    protected static function resolveOrCreateSetInCollection(int $collectionId, int $setId): int
    {
        $setInCollection = SetsInCollection::firstOrCreate([
            'collection_management_id' => $collectionId,
            'set_id' => $setId,
        ]);

        return $setInCollection->id;
    }

    public static function normalizeSetName(string $raw): string
    {
        return trim(preg_replace('/\s+(Variants|Extras|Alternate)$/i', '', $raw));
    }
}