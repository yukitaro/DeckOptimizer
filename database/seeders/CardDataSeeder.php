<?php

namespace Database\Seeders;

use League\Csv\Reader;

use App\Models\CardData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('data/AllPrintingsCSVFiles_0906/cards.csv');
        $csv = Reader::createFromPath($csvPath, 'r');

        // Set the header offset if your CSV has a header row (e.g., first row is headers)
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        if (empty($records)) {
            echo 'Well that\'s a problem\n';
        }

        foreach ($records as $record) {
            //$aParsedMagicSet = this.createNewSet($record);
            $aParsedCardData = new CardData([
                'name' => $record['name'],
                //'set_name' => $record['set_name'], Will need to populate this over from set_data
                'official_set_id' => $record['setCode'],
                'card_id' => $record['number'],
                'card_uuid' => $record['uuid'],
                'colors' => $record['colors'],
                'colorIdentities' => $record['colorIdentity'],
                'keywords' => $record['keywords'],
                'mana_cost' => $record['manaCost'],
                'mana_value' => $record['manaValue'],
                'power' => $record['power'],
                'printings' => $record['printings'],
                'rarity' => $record['rarity'],
                'text' => $record['text'],
                'toughness' => $record['toughness'],
                'type' => $record['type'],
                'types' => $record['types']
            ]);
            //$aMagicSet = app\Models\MagicSetData;
            //$aMagicSet->set_name = $aParsedMagicSet->set_name;
            //DB::table('magic_sets')->insert($aParsedMagicSet);
            $aParsedCardData->save();
        }        
    }
}
