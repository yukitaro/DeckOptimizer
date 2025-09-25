<?php

namespace Database\Seeders;

use League\Csv\Reader;

use App\Models\CardData;
use App\Models\CardDataNormalized;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CardDataNormalizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uniqueCards = CardData::where('rarity', 'common')
            ->groupBy('name')
            ->select([
                'name',
                DB::raw('MIN(type) as type'),
                DB::raw('MIN(colors) as colors'),
                DB::raw('MIN(mana_cost) as mana_cost'),
                DB::raw('MIN(rarity) as rarity'),
                DB::raw('MIN(text) as text'),
                DB::raw('MIN(power) as power'),
                DB::raw('MIN(toughness) as toughness'),
                DB::raw('MIN(image_url) as image_url'),
            ])
            ->get();

        if ($uniqueCards->isEmpty()) {
            echo 'Well that\'s a problem\n';
        } else {
        
        }

        foreach ($uniqueCards as $card) {
            //$aParsedMagicSet = this.createNewSet($record);
            $aCardData = new CardDataNormalized([
                'name' => $card->name,
                'type' => $card->type,
                'colors' => $card->colors,
                'mana_cost' => $card->mana_cost,
                'rarity' => $card->rarity,
                'text' => $card->text,
                'power' => $card->power,
                'toughness' => $card->toughness,
                'image_url_to_use' => $card->image_url,
            ]);
            //$aMagicSet = app\Models\MagicSetData;
            //$aMagicSet->set_name = $aParsedMagicSet->set_name;
            //DB::table('magic_sets')->insert($aParsedMagicSet);
            $aCardData->save();
        }        
    }
}
