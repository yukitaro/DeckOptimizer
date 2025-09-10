<?php

namespace Database\Seeders;

use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CardDataFromSetDataNormalizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uniqueCards = CardDataFromSetData::groupBy('name')
            ->select([
                'name',
                DB::raw('MIN(type) as type'),
                DB::raw('MIN(colors) as colors'),
                DB::raw('MIN(mana_cost) as mana_cost'),
                DB::raw('MIN(rarity) as rarity'),
                DB::raw('MIN(text) as text'),
                DB::raw('MIN(power) as power'),
                DB::raw('MIN(power) as rarity'),
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
            if ($card)
            $aCardData = new CardDataNormalized([
                'name' => $card->name,
                'type' => $card->type,
                'colors' => $card->colors,
                'mana_cost' => empty($card->mana_cost) ? "0" : $card->mana_cost,
                'rarity' => $card->rarity,
                'text' => $card->text,
                'power' => $card->power,
                'rarity' => $card->rarity,
                'toughness' => $card->toughness,
                'image_url_to_use' => $card->image_url
            ]);
            //$aMagicSet = app\Models\MagicSetData;
            //$aMagicSet->set_name = $aParsedMagicSet->set_name;
            //DB::table('magic_sets')->insert($aParsedMagicSet);
            $aCardData->save();
        }        
    }
}


