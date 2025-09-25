<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SetData;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;

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
        $jsonDataPath = base_path('data/AllSetFiles');

        $jsonSetData = glob($jsonDataPath . '/*.json'); 

        foreach ($jsonSetData as $aSetData) {
            if (is_file($aSetData)) {
                $jsonPointerToSetData = JsonParser::parse($aSetData)->pointer('/data');

                foreach ($jsonPointerToSetData as $key => $value) {
                // Inside the loop, $key and $value are the parsed elements.
                //echo "Key: $key";//, Value: $value\n";
                    $aParsedSetData = new SetData([
                        'set_name' =>  $value['code'],                   
                        'official_set_code' => $value['name'],
                        'release_date' => $value['releaseDate'],
                        'total_cards' => $value['totalSetSize']
                    ]);  

                    $aParsedSetData->save();
                    $jsonCardData = JsonParser::parse($aSetData)->pointer('/data/cards');

                    foreach ($jsonCardData as $key => $aCardData) {
                        if (is_array($aCardData)) {
                            foreach ($aCardData as $aCardFromSet) {
                                $identifiers = $aCardFromSet['identifiers'] ?? [];
                                $multiverseId = $identifiers['multiverseId'] ?? null;
                                $purchaseUrls = $aCardFromSet['purchaseUrls'] ?? [];

                                $imageUrl = $multiverseId ? "https://gatherer.wizards.com/Handlers/Image.ashx?multiverseid=" . $multiverseId . "&type=card" : null;

                                $metadata = new CardMetadata([
                                    'cardKingdomId' => $identifiers['cardKingdomId'] ?? null,
                                    'multiverseId' => $multiverseId,
                                    'scryfallId' => $identifiers['scryfallId'] ?? null,
                                    'tcgplayerProductId' => $identifiers['tcgplayerProductId'] ?? null,
                                    'tcgplayerPurchaseUrl' => $purchaseUrls['tcgplayer'] ?? null
                                ]);
                                $metadata->save();

                                $aParsedSetData->cardsInSet()->create([
                                    'name' => $this->getField($aCardFromSet, 'name'),
                                    'set_name' => $aParsedSetData['set_name'],
                                    'magic_set_data_id' => $aParsedSetData->id,
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
                                    'set_code' => $aParsedSetData['setCode'],
                                    'text' => $this->getField($aCardFromSet, 'text'),
                                    'toughness' => $this->getField($aCardFromSet, 'toughness'),
                                    'type' => $this->getField($aCardFromSet, 'type'),
                                    'types' => $this->getField($aCardFromSet, 'types', true),
                                    'image_url' => $imageUrl,
                                ]);
                            }
                        }
                    }
                }
            }        
        }
    }
}
