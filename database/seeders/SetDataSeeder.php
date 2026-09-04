<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\SetData;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use App\Models\SetEnrichmentStatus;
use App\Models\MtgImageLookup;
use App\Models\CardDataNormalized;
use App\Models\MtgJsonImportCandidate;

use App\Services\CardMetadataEnricher;
use App\Services\ScryfallImageHydratorService;

use Cerbero\JsonParser\JsonParser;
use Laravel\Telescope\Telescope;

class SetDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        Telescope::withoutRecording(function () {
            $forceMode = getenv('SEEDER_FORCE') === 'true';
            $slugOnly = getenv('SEEDER_SLUG_ONLY') === 'true';
            $skipImageHydration = getenv('SEEDER_SKIP_SCRYFALL_IMAGE_HYDRATION') === 'true';

            $onlySets = collect(explode(',', getenv('SEEDER_SETS') ?: ''))
                ->map(fn($s) => strtoupper(trim($s)))
                ->filter()
                ->values()
                ->toArray();

            $hydrator = app(ScryfallImageHydratorService::class);
            if (!$skipImageHydration) {
                Log::info("🔄 Seeding image lookups...");
                $count = $hydrator->seedImageLookups($onlySets, force: true);
                Log::info("✅ Seeded {$count} image lookups.");
            }

            $jsonFiles = glob(base_path('data/AllSetFiles/*.json'));
            $normalizedSeen = [];

            foreach ($jsonFiles as $file) {
                $setCode = strtoupper(basename($file, '.json'));
                if ($onlySets && !in_array($setCode, $onlySets)) {
                    Log::info("⏭️ Skipping $setCode");
                    continue;
                }

                Log::info("📂 Processing $setCode");

                $setPointer = JsonParser::parse($file)->pointer('/data');
                foreach ($setPointer as $setData) {
                    $setCode = strtoupper($setData['code'] ?? '');
                    if ($onlySets && !in_array($setCode, $onlySets)) continue;

                    Log::info("DEBUG Set Level - Code: {$setCode}, ReleaseDate: " . ($setData['releaseDate'] ?? 'NULL'));

                    $existingSet = SetData::updateOrCreate(
                        ['set_name' => $setCode],
                        [
                            'official_set_code' => $setData['name'],
                            'release_date'      => $setData['releaseDate'] ?? null,
                            'total_cards'       => $setData['totalSetSize'] ?? null,
                        ]
                    );

                    if ($forceMode || !$existingSet->cards_populated) {
                        Log::info("📦 Seeding cards for $setCode");

                        $cardsArray = $setData['cards'] ?? [];
                        Log::info("DEBUG Cards Array Count: " . count($cardsArray));

                        // Wrap all database operations for this set in a single transaction
                        DB::transaction(function () use (
                            $cardsArray, 
                            $existingSet, 
                            $setCode, 
                            $slugOnly, 
                            &$normalizedSeen, 
                            $setData
                        ) {
                            // Pre-load existing cards for this set into an in-memory key-value map
                            $existingCards = $existingSet->cardsInSet()
                                ->get()
                                ->keyBy('card_uuid');

                            // Pre-load Scryfall image lookups in a single batch query
                            $scryfallIds = collect($cardsArray)
                                ->pluck('identifiers.scryfallId')
                                ->filter()
                                ->unique()
                                ->toArray();

                            $imageLookups = !empty($scryfallIds)
                                ? MtgImageLookup::whereIn('card_uuid', $scryfallIds)->get()->keyBy('card_uuid')
                                : collect();

                            $processedCount = 0;

                            foreach ($cardsArray as $cardJson) {
                                if (!is_array($cardJson) || !isset($cardJson['uuid'])) {
                                    Log::warning("DEBUG: Invalid card element encountered for $setCode");
                                    continue;
                                }

                                $processedCount++;

                                if ($slugOnly) {
                                    $slug = $this->makeSlug($cardJson['name']);
                                    CardDataFromSetData::where([
                                        ['card_uuid', $cardJson['uuid']],
                                        ['set_name', $setCode],
                                        ['number_in_set', $cardJson['number']],
                                    ])->update(['slug' => $slug]);
                                    continue;
                                }

                                $identifiers = $cardJson['identifiers'] ?? [];
                                $metadata = $this->findOrCreateMetadata($identifiers, $cardJson['purchaseUrls'] ?? [], $cardJson);
                                $slug = $this->makeSlug($cardJson['name']);

                                $frontImage = $cardJson['image_uris']['normal']
                                    ?? ($cardJson['card_faces'][0]['image_uris']['normal'] ?? null);

                                $releaseDateToUse = $cardJson['originalReleaseDate'] 
                                    ?? $cardJson['releaseDate'] 
                                    ?? $existingSet->release_date 
                                    ?? null;

                                $cardAttributes = [
                                    'name'                => $cardJson['name'],
                                    'magic_set_data_id'   => $existingSet->id,
                                    'card_uuid'           => $cardJson['uuid'],
                                    'set_name'            => $setCode,
                                    'number_in_set'       => $cardJson['number'],
                                    'card_multiverse_id'  => $identifiers['multiverseId'] ?? null,
                                    'colors'              => implode(',', $cardJson['colors'] ?? []),
                                    'colorIdentities'     => implode(',', $cardJson['colorIdentity'] ?? []),
                                    'keywords'            => implode(',', $cardJson['keywords'] ?? []),
                                    'mana_cost'           => $cardJson['manaCost'] ?? null,
                                    'mana_value'          => $cardJson['manaValue'] ?? null,
                                    'card_metadata_id'    => $metadata->id,
                                    'power'               => $cardJson['power'] ?? null,
                                    'printings'           => implode(',', $cardJson['printings'] ?? []),
                                    'rarity'              => $cardJson['rarity'] ?? null,
                                    'release_date'        => $releaseDateToUse,
                                    'text'                => $cardJson['text'] ?? null,
                                    'toughness'           => $cardJson['toughness'] ?? null,
                                    'type'                => $cardJson['type'] ?? null,
                                    'types'               => implode(',', $cardJson['types'] ?? []),
                                    'image_url'           => $frontImage,
                                    'image_normalized_at' => $frontImage ? now() : null,
                                    'slug'                => $slug,
                                ];

                                // Instant update or create using pre-loaded map
                                $cardRow = $existingCards->get($cardJson['uuid']);
                                if ($cardRow) {
                                    $cardRow->update($cardAttributes);
                                } else {
                                    $cardRow = $existingSet->cardsInSet()->create($cardAttributes);
                                    $existingCards->put($cardJson['uuid'], $cardRow);
                                }

                                $normalizedName = strtolower(trim($cardRow->name));
                                if (!isset($normalizedSeen[$normalizedName])) {
                                    $scryfallId = $identifiers['scryfallId'] ?? null;
                                    $lookup = $scryfallId ? $imageLookups->get($scryfallId) : null;

                                    $image = $frontImage ?? $lookup?->canonical_image_url ?? $cardRow->image_url;

                                    CardDataNormalized::updateOrCreate(
                                        ['normalized_name' => $normalizedName, 'set_code' => $setCode],
                                        [
                                            'source_printing_id' => $cardRow->id,
                                            'source_printing'    => $cardRow->number_in_set,
                                            'printings'          => implode(',', $cardJson['printings'] ?? []),
                                            'image_url_to_use'   => $image,
                                        ]
                                    );

                                    $normalizedSeen[$normalizedName] = true;
                                }

                                $metadata->card_data_from_set_data_id = $cardRow->id;
                                $metadata->save();
                            }

                            Log::info("DEBUG Total Cards Processed for $setCode: $processedCount");

                            $existingSet->update([
                                'cards_populated' => true,
                                'imported_from_mtgjson' => true,
                                'date_of_json_used_for_import' => $setData['meta']['date'] ?? now(),
                            ]);

                            MtgJsonImportCandidate::where('set_code', $setCode)
                                ->update(['imported_into_database' => true]);

                            SetEnrichmentStatus::updateOrCreate(
                                ['set_code' => $setCode],
                                ['enriched_at' => now(), 'logic_version' => 'v1']
                            );
                        });

                        Log::info("✅ Finished $setCode");
                    } else {
                        Log::info("⏭️ Skipping $setCode (already populated)");
                    }
                }
            }

            Log::info("🔄 Hydrating image links...");
            $result = $hydrator->hydrateLinkedTables($onlySets, force: true);
            Log::info("✅ Updated: {$result['updated']} printings, {$result['normalizedUpdated']} normalized");
        });
    }

    private function makeSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = str_replace(["’s", "'s"], "s", $slug);
        $slug = str_replace([' // ', '//'], '--', $slug);
        $slug = preg_replace('/[^\p{L}\p{N}-]+/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    private function findOrCreateMetadata(array $ids, array $urls, array $json): CardMetadata
    {
        $query = CardMetadata::query();

        if (!empty($ids['scryfallId'])) {
            $query->where('scryfallId', $ids['scryfallId']);
        } elseif (!empty($ids['multiverseId'])) {
            $query->where('multiverseId', $ids['multiverseId']);
        } elseif (!empty($ids['tcgplayerProductId'])) {
            $query->where('tcgplayerProductId', $ids['tcgplayerProductId']);
        }

        $metadata = $query->first() ?? new CardMetadata();

        if (!empty($ids['scryfallId'])) {
            $metadata->scryfallId = $ids['scryfallId'];
        }
        if (!empty($ids['multiverseId'])) {
            $metadata->multiverseId = $ids['multiverseId'];
        }
        if (!empty($ids['cardKingdomId'])) {
            $metadata->cardKingdomId = $ids['cardKingdomId'];
        }
        if (!empty($ids['tcgplayerProductId'])) {
            $metadata->tcgplayerProductId = $ids['tcgplayerProductId'];
        }
        if (!empty($urls['tcgplayer'])) {
            $metadata->tcgplayerPurchaseUrl = $urls['tcgplayer'];
        }

        CardMetadataEnricher::enrich($json, $metadata);

        $metadata->save();

        return $metadata;
    }
}