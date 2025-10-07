<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\MtgBulkPrices;

class MtgBulkPriceController extends Controller
{
    public function fetchPrices(Request $request)
    {
        $cardDataIds = $request->input('card_data_normalized_ids', []);
        $results = [];

        $normalizedCards = CardDataNormalized::with('sourceCard.cardMetadata', 'sourceCard.setData')
            ->whereIn('id', $cardDataIds)
            ->get();

        foreach ($normalizedCards as $card) {
            $cardData = $card->sourceCard;
            if (!$cardData || !$cardData->cardMetadata || !$cardData->setData) continue;

            $setCodes = explode(',', $cardData->printings);

            $printings = CardDataFromSetData::whereIn('set_name', $setCodes)
                ->where('name', $cardData->name)
                ->with(['cardMetadata', 'setData'])
                ->get();

            $records = [];

            foreach ($printings as $printing) {
                $scryfallId = optional(json_decode($printing->cardMetadata->identifiers))->scryfallId;
                $tcgPlayerPurchaseUrl = optional(json_decode($printing->cardMetadata->purchaseUrls))->tcgplayer;
                if (!$scryfallId) continue;

                $bulk = MtgBulkPrices::where('scryfall_id', $scryfallId)->first();
                if (!$bulk || !$bulk->usd) continue;

                $records[] = (object)[
                    'price' => $bulk->usd,
                    'is_foil' => false,
                    'currency' => 'usd',
                    'source' => 'bulk',
                    'price_date' => $bulk->price_date,
                    'normalized_card_id' => $card->id,
                    'card_data_id' => $cardData->id,
                    'set_code' => $printing->set_name,
                    'scryfall_id' => $scryfallId,
                    'tcg_player_link' => $tcgPlayerPurchaseUrl
                ];
            }

            $records = collect($records)
                ->where('is_foil', false)
                ->sortBy('price')
                ->take(3)
                ->values()
                ->all();

            $results[$card->id] = $records;
        }

        //return response()->json($results);
        return response()->json(array_merge(...array_values($results)));
    }
}
