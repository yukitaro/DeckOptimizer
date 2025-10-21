<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SetData;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use App\Models\SetEnrichmentStatus;

use App\Services\CardMetadataEnricher;

use Cerbero\JsonParser\JsonParser;
use function Cerbero\JsonParser\JsonParser\parseJson;

class SetDataSeeder extends Seeder
{
    private function getField($array, $key, $implode = false, $default = null) {
        if (!isset($array[$key])) return $default;
        if ($implode && is_array($array[$key])) return implode(',', $array[$key]);
        return $array[$key];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {        
        $onlySetsRaw = getenv('SEEDER_SETS');
        $onlySets = collect(explode(',', $onlySetsRaw ?: ''))
            ->map(fn($s) => strtoupper(trim($s)))
            ->filter()
            ->toArray();

        $filterEnabled = count($onlySets) > 0;

        \Log::info("Seeder mode: " . ($filterEnabled ? "Filtered by SEEDER_SETS" : "Full import"));

        $jsonDataPath = base_path('data/AllSetFiles');
        $jsonSetData = glob($jsonDataPath . '/*.json'); 
        $normalizedSeen = [];        

        foreach ($jsonSetData as $aSetData) {
            if (!is_file($aSetData)) {
                \Log::warning("⚠️ Skipping non-file: $aSetData");
                continue;
            }

            \Log::info("📂 Processing file: $aSetData");

            $jsonPointerToSetData = JsonParser::parse($aSetData)->pointer('/data');

            foreach ($jsonPointerToSetData as $key => $value) {
                $setCode = strtoupper($value['code'] ?? '');

                if (!empty($onlySets) && !in_array($setCode, $onlySets)) {
                    \Log::info("⏭️ Skipping $setCode (not in SEEDER_SETS)");
                    continue;
                }

                $logicVersion = 'v1';

                $existingSet = SetData::where('set_name', $setCode)->first();

                if (!$existingSet) {
                    \Log::info("🆕 Creating SetData for $setCode");
                    $existingSet = new SetData([
                        'set_name' => $value['code'],                   
                        'official_set_code' => $value['name'],
                        'release_date' => $value['releaseDate'],
                        'total_cards' => $value['totalSetSize'],
                        'cards_populated' => false
                    ]);
                    $existingSet->save();
                }

                if (!$existingSet->cards_populated) {
                    \Log::info("📦 Populating cards for $setCode");

                    $jsonCardData = JsonParser::parse($aSetData)->pointer('/data/cards');
                    $cardCount = 0;

                    foreach ($jsonCardData as $key => $aCardData) {
                        if (is_array($aCardData)) {
                            foreach ($aCardData as $aCardFromSet) {
                                $cardCount++;
                                $identifiers = $aCardFromSet['identifiers'] ?? [];
                                $multiverseId = $identifiers['multiverseId'] ?? null;
                                $purchaseUrls = $aCardFromSet['purchaseUrls'] ?? [];

                                $scryfallId = $identifiers['scryfallId'] ?? null;
                                $imageUrl = null;

                                if ($scryfallId) {
                                    $lookup = MtgImageLookup::where('card_uuid', $scryfallId)->first();
                                    $imageUrl = $lookup?->canonical_image_url;
                                }

                                $metadata = $this->findOrCreateMetadata($identifiers, $purchaseUrls, $aCardFromSet);

                                $cardRow = $existingSet->cardsInSet()->create([
                                    'name' => $this->getField($aCardFromSet, 'name'),
                                    'set_name' => $existingSet['set_name'],
                                    'magic_set_data_id' => $existingSet->id,
                                    'number_in_set' => $this->getField($aCardFromSet, 'number'),
                                    'card_uuid' => $this->getField($aCardFromSet, 'uuid'),
                                    'card_multiverse_id' => $multiverseId,
                                    'colors' => $this->getField($aCardFromSet, 'colors', true),
                                    'colorIdentities' => $this->getField($aCardFromSet, 'colorIdentity', true),
                                    'keywords' => $this->getField($aCardFromSet, 'keywords', true),
                                    'mana_cost' => $this->getField($aCardFromSet, 'manaCost'),
                                    'mana_value' => $this->getField($aCardFromSet, 'manaValue'),
                                    'card_metadata_id' => $metadata->id,
                                    'power' => $this->getField($aCardFromSet, 'power'),
                                    'printings' => $this->getField($aCardFromSet, 'printings', true),
                                    'rarity' => $this->getField($aCardFromSet, 'rarity'),
                                    'text' => $this->getField($aCardFromSet, 'text'),
                                    'toughness' => $this->getField($aCardFromSet, 'toughness'),
                                    'type' => $this->getField($aCardFromSet, 'type'),
                                    'types' => $this->getField($aCardFromSet, 'types', true),
                                    'image_url' => $imageUrl,
                                ]);

                                $normalizedName = strtolower(trim($cardRow->name));

                                if (!isset($normalizedSeen[$normalizedName])) {
                                    \App\Models\CardDataNormalized::updateOrCreate(
                                        [
                                            'normalized_name' => $normalizedName,
                                            'set_code' => $existingSet->set_name,
                                        ],
                                        [
                                            'source_printing_id' => $cardRow->id,
                                            'source_printing' => $cardRow->number_in_set,
                                            'printings' => $this->getField($aCardFromSet, 'printings', true),
                                            'image_url_to_use' => $lookup?->canonical_image_url ?? $cardRow->image_url,
                                        ]
                                    );

                                    $normalizedSeen[$normalizedName] = true;
                                }

                                $metadata->card_data_from_set_data_id = $cardRow->id;
                                $metadata->save();
                            }
                        }
                    }

                    \Log::info("✅ Imported $cardCount cards for $setCode");

                    $existingSet->cards_populated = true;
                    $existingSet->imported_from_mtgjson = true;
                    $existingSet->date_of_json_used_for_import = $json['meta']['date'] ?? now();
                    $existingSet->save();

                    \App\Models\MtgJsonImportCandidate::where('set_code', $setCode)
                        ->update(['imported_into_database' => true]);

                    SetEnrichmentStatus::updateOrCreate(
                        ['set_code' => $setCode],
                        ['enriched_at' => now(), 'logic_version' => $logicVersion]
                    );

                    \Log::info("🎯 Finished enrichment for $setCode");
                } else {
                    $this->command->line("⏭️ Skipping $setCode (already populated)");
                }
            }
        }
    }

    private function findOrCreateMetadata(array $identifiers, array $purchaseUrls, array $cardJson): CardMetadata
    {
        $scry = $identifiers['scryfallId'] ?? null;
        $multi = $identifiers['multiverseId'] ?? null;
        $tcg = $identifiers['tcgplayerProductId'] ?? null;

        // Try to find existing metadata by identifiers
        $query = CardMetadata::query();
        if ($scry) {
            $query->where('scryfallId', $scry);
        } elseif ($multi) {
            $query->where('multiverseId', $multi);
        } elseif ($tcg) {
            $query->where('tcgplayerProductId', $tcg);
        }

        $metadata = $query->first();
        
        if (!$metadata) {
            $metadata = new CardMetadata();
        }

        // Direct assignment instead of using closure with reference
        if ($scry !== null && $scry !== '') {
            $metadata->scryfallId = $scry;
        }
        if ($multi !== null && $multi !== '') {
            $metadata->multiverseId = $multi;
        }
        if (isset($identifiers['cardKingdomId']) && $identifiers['cardKingdomId'] !== '') {
            $metadata->cardKingdomId = $identifiers['cardKingdomId'];
        }
        if ($tcg !== null && $tcg !== '') {
            $metadata->tcgplayerProductId = $tcg;
        }
        if (isset($purchaseUrls['tcgplayer']) && $purchaseUrls['tcgplayer'] !== '') {
            $metadata->tcgplayerPurchaseUrl = $purchaseUrls['tcgplayer'];
        }

        // Run enrichment
        CardMetadataEnricher::enrich($cardJson, $metadata);

        $metadata->save();

        return $metadata;
    }
}
