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
        $uniqueCardNames = CardDataFromSetData::distinct()->pluck('name');
    
        foreach ($uniqueCardNames as $cardName) {
            // Get the "canonical" printing (deterministic selection)
            $sourceCard = CardDataFromSetData::where('name', $cardName)
                ->with('cardMetadata')
                ->orderBy('id') // Or orderBy('set_name') for preference
                ->first();

            CardDataNormalized::create([
                'name' => $sourceCard->name,
                'type' => $sourceCard->type,
                'colors' => $sourceCard->colors,
                'mana_cost' => empty($sourceCard->mana_cost) ? "0" : $sourceCard->mana_cost,
                'text' => $sourceCard->text,
                'power' => $sourceCard->power,
                'rarity' => $sourceCard->rarity,
                'set_code' => $sourceCard->set_code,
                'toughness' => $sourceCard->toughness,
                'image_url_to_use' => $sourceCard->image_url,
                'printings' => implode(',', CardDataFromSetData::where('name', $cardName)->pluck('set_name')->toArray()),
                'source_printing_id' => $sourceCard->id
            ]);
        }
    }
}


