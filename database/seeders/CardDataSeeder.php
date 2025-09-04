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
        $csvPath = base_path('data/all_mtg_cards_09012025.csv');
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
                'set_name' => $record['set_name'],
                'official_set_id' => $record['set'],
                'card_id' => $record['number'],
                'card_uuid' => $record['id'],
                'card_multiverse_id' => $record['multiverse_id'],
                'type' => $record['type'],
                'colors' => $record['colors'],
                'mana_cost' => $record['mana_cost'],
                'rarity' => $record['rarity'],
                'text' => $record['text'],
                'power' => $record['power'],
                'toughness' => $record['toughness'],
                'image_url' => $record['image_url']
            ]);
            //$aMagicSet = app\Models\MagicSetData;
            //$aMagicSet->set_name = $aParsedMagicSet->set_name;
            //DB::table('magic_sets')->insert($aParsedMagicSet);
            $aParsedCardData->save();
        }        
    }
}
