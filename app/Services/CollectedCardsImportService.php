<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\CollectedCardService;

use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use App\Models\SetData;
use App\Models\SetsInCollection;

use App\Services\VariantCardResolver;

class CollectedCardsImportService 
{
    protected static array $knownFormats = [
        'manabox' => [
            'card_name' => 'Name',
            'set_code' => 'Set code',
            'scryfall_id' => 'Scryfall ID',
            'card_count' => 'Quantity',
            'condition' => 'Condition',
            'is_foil' => 'Foil',
            'purchase_price' => 'Purchase price',
            'printing_variant' => null,
            'storage_location' => null,
            'finishes' => 'Foil',
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
            'finishes' => null,
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
            'finishes' => null,
        ]
    ];

    protected static function detectFormat(array $headerRow): ?array
    {
        foreach (static::$knownFormats as $format => $map) {
            $matched = collect($map)->filter(fn($header) => $header && in_array($header, $headerRow));
            Log::info("Format check: {$format}", [
                'matched_headers' => $matched->all(),
                'match_count' => $matched->count()
            ]);

            if ($matched->count() >= 3) {
                Log::info("Detected format: {$format}", ['map' => $map]);
                return $map;
            }
        }

        Log::warning('No matching CSV format found');
        return null;
    }

    public static function importToCollection(string $mode, array $records, int $collectionId, bool $shouldDedupe): void
    {
        Log::info('Import trigger', [
            'collectionId' => $collectionId,
            'recordCount' => count($records),
            'mode' => $mode
        ]);

        if (empty($records)) {
            throw new \Exception('No records provided for import');
        }

        $start = microtime(true);

        \DB::transaction(function () use ($mode, $records, $collectionId, $shouldDedupe, $start) {

            if ($mode === 'set') {
                $setIds = SetsInCollection::where('collection_management_id', $collectionId)->pluck('id');

                if ($setIds->isNotEmpty()) {
                    \DB::table('collected_cards')->whereIn('set_in_collection_id', $setIds)->delete();
                    SetsInCollection::whereIn('id', $setIds)->delete();
                }
            }

            // HEADER MAP
            $headerMap = static::detectFormat(array_keys($records[0]));
            if (!$headerMap || empty($headerMap['set_code']) || empty($headerMap['card_name']) || empty($headerMap['card_count'])) {
                throw new \Exception('CSV missing required set_code/card_name/card_count column mapping');
            }

            $setCodeColumn = $headerMap['set_code'];

            $grouped = collect($records)->groupBy($setCodeColumn);

            // Normalize CSV set codes
            $normalizeCode = fn($c) => strtoupper(trim((string)$c));
            $allCodes = $grouped->keys()->map($normalizeCode)->unique()->values();

            // Lookup SetData
            $setIdByCode = SetData::whereIn('set_name', $allCodes)
                ->pluck('id', 'set_name');

            foreach ($grouped as $csvSetCodeRaw => $recordsForSet) {

                $csvSetCode = $normalizeCode($csvSetCodeRaw);
                $setId = $setIdByCode[$csvSetCode] ?? null;

                if (!$setId) {
                    Log::warning('Unresolved SetData for CSV set code', [
                        'csvSetCodeRaw' => $csvSetCodeRaw,
                        'normalized' => $csvSetCode
                    ]);
                    continue;
                }

                $setInCollectionId = static::resolveOrCreateSetInCollection($collectionId, $setId);

                // DEDUPE
                if ($shouldDedupe) {
                    $rowsForSet = static::dedupeRowsForSet($recordsForSet, $headerMap);
                } else {
                    $rowsForSet = $recordsForSet;
                }

                // CARD LOOKUP
                $nameToCardId = CardDataFromSetData::where('set_name', $csvSetCode)
                    ->get(['id', 'name'])
                    ->reduce(function ($carry, $row) {
                        $carry[mb_strtolower(trim($row->name))] = $row->id;
                        return $carry;
                    }, []);

                $payloads = [];
                $processedCount = 0;
                $skippedCount = 0;

                foreach ($rowsForSet as $idx => $record) {
                    $cardName = trim((string)($record[$headerMap['card_name']] ?? ''));
                    $count = (int)($record[$headerMap['card_count']] ?? 0);

                    if ($cardName === '' || $count <= 0) {
                        $skippedCount++;
                        continue;
                    }

                    $cardId = null;

                    // Scryfall ID lookup
                    $scryfallId = $record[$headerMap['scryfall_id']] ?? null;

                    if ($scryfallId) {
                        $meta = CardMetadata::where('scryfall_id', $scryfallId)->first();

                        if ($meta) {
                            $cardId = $meta->card_data_from_set_data_id;
                        }
                    }

                    // Name fallback
                    if (!$cardId) {
                        $cardId = $nameToCardId[mb_strtolower($cardName)] ?? null;
                    }

                    if (!$cardId) {
                        Log::error('Card lookup failed', [
                            'cardName' => $cardName,
                            'setCode' => $csvSetCode,
                            'scryfallId' => $scryfallId,
                            'availableNames' => array_keys($nameToCardId)
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $finishes = !empty($headerMap['finishes']) ? ($record[$headerMap['finishes']] ?? null) : null;
                    $normalizedAttributes = !empty($finishes) ? json_encode(['finishes' => (array) $finishes]) : null;
                    $foilRaw = strtolower(trim($headerMap['is_foil'] ? ($record[$headerMap['is_foil']] ?? '') : ''));
                    $isEtched = str_contains($foilRaw, 'etched');

                    // Build attributes
                    $attributes = [
                        'set_in_collection_id'  => $setInCollectionId,
                        'card_data_id'          => $cardId,
                        'card_count'            => $count,
                        'condition'             => $headerMap['condition'] ? ($record[$headerMap['condition']] ?? null) : null,
                        'is_foil'               => $isEtched || static::parseBoolish($foilRaw),
                        'printing_variant'      => $headerMap['printing_variant'] ? ($record[$headerMap['printing_variant']] ?? null) : null,
                        'purchase_price'        => $headerMap['purchase_price'] ? (($record[$headerMap['purchase_price']] ?? null) ?: null) : null,
                        'storage_location'      => $headerMap['storage_location'] ? ($record[$headerMap['storage_location']] ?? null) : null,
                        'normalized_attributes' => $normalizedAttributes,
                    ];

                    if ($mode === 'merge') {
                        CollectedCardService::handle('merge', $attributes);
                        $processedCount++;
                    } else {
                        $now = now()->toDateTimeString();
                        $payloads[] = array_merge($attributes, [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $processedCount++;
                    }
                }

                if ($mode === 'set' && !empty($payloads)) {
                    Log::info('SET mode: inserting payload batch', ['payloadCount' => count($payloads)]);
                    Log::info('Payload data: ', ['payloads' => $payloads]);
                    \DB::table('collected_cards')->insert($payloads);
                }

                Log::info('Set import summary', [
                    'set_code_input' => $csvSetCodeRaw,
                    'normalized_code' => $csvSetCode,
                    'set_id' => $setId,
                    'sets_in_collection_id' => $setInCollectionId,
                    'processed_in_set' => $processedCount,
                    'skipped_in_set' => $skippedCount,
                    'rows_for_set' => count($recordsForSet),
                    'rows_after_dedupe' => count($rowsForSet)
                ]);
            }

            $duration = round(microtime(true) - $start, 2);
            Log::info('Import complete', [
                'collectionId' => $collectionId,
                'duration_seconds' => $duration
            ]);
        });
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

    protected static function parseBoolish($value): bool
    {
        $v = strtolower(trim((string)$value));
        $result = in_array($v, ['1','y','yes','true','foil'], true);

        return $result;
    }

    protected static function dedupeRowsForSet(iterable $rows, array $headerMap): array
    {
        $map = [];

        foreach ($rows as $rowIndex => $row) {
            $name = trim((string)($row[$headerMap['card_name']] ?? ''));
            $count = (int)($row[$headerMap['card_count']] ?? 0);

            if ($name === '' || $count <= 0) continue;

            $key = mb_strtolower($name) . '|' . ($row[$headerMap['scryfall_id']] ?? '');

            $isFoil = static::parseBoolish($headerMap['is_foil'] ? ($row[$headerMap['is_foil']] ?? null) : null);
            $condition = $headerMap['condition'] ? ($row[$headerMap['condition']] ?? null) : null;
            $purchase = $headerMap['purchase_price'] ? (($row[$headerMap['purchase_price']] ?? null) ?: null) : null;
            $storage = $headerMap['storage_location'] ? ($row[$headerMap['storage_location']] ?? null) : null;

            if (!isset($map[$key])) {
                $map[$key] = [
                    'row' => $row,
                    '__name' => $name,
                    '__count' => $count,
                    '__is_foil' => $isFoil,
                    '__condition' => $condition,
                    '__purchase' => $purchase,
                    '__storage' => $storage,
                ];
            } else {
                $map[$key]['__count'] += $count;
                $map[$key]['__is_foil'] = $map[$key]['__is_foil'] || $isFoil;

                if (empty($map[$key]['__condition']) && !empty($condition)) {
                    $map[$key]['__condition'] = $condition;
                }

                if ($purchase !== null && is_numeric($purchase)) {
                    $existing = $map[$key]['__purchase'];
                    $existingNum = is_numeric($existing) ? (float)$existing : null;

                    if ($existingNum === null || (float)$purchase > $existingNum) {
                        $map[$key]['__purchase'] = $purchase;
                    }
                }

                if (empty($map[$key]['__storage']) && !empty($storage)) {
                    $map[$key]['__storage'] = $storage;
                }
            }
        }

        $out = [];
        foreach ($map as $item) {
            $row = $item['row'];
            $row[$headerMap['card_name']] = $item['__name'];
            $row[$headerMap['card_count']] = $item['__count'];
            if ($headerMap['is_foil']) {
                $row[$headerMap['is_foil']] = $item['__is_foil'] ? 'yes' : 'no';
            }
            if ($headerMap['condition']) {
                $row[$headerMap['condition']] = $item['__condition'];
            }
            if ($headerMap['purchase_price']) {
                $row[$headerMap['purchase_price']] = $item['__purchase'];
            }
            if ($headerMap['storage_location']) {
                $row[$headerMap['storage_location']] = $item['__storage'];
            }
            $out[] = $row;
        }

        return $out;
    }
}
