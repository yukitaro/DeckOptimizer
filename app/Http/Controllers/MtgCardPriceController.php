<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\MtgCardPrices;
use App\Models\MtgCardToSetPrice;
use App\Services\MtgCardPriceFetcher;

class MtgCardPriceController extends Controller
{
    public function fetchPrices(Request $request)
    {
        $cardDataIds = $request->input('card_data_normalized_ids', []);
        $today = now()->toDateString();
        $results = [];

        $normalizedCards = CardDataNormalized::with('sourceCard.cardMetadata', 'sourceCard.setData')
            ->whereIn('id', $cardDataIds)
            ->get();

        foreach ($normalizedCards as $card) {
            $cardData = $card->sourceCard;
            if (!$cardData) continue;

            $meta = $cardData->cardMetadata;
            $set = $cardData->setData;
            if (!$meta || !$set) continue;


            $setCodes = explode(',', $card->sourceCard->printings);

            $printings = CardDataFromSetData::whereIn('set_name', $setCodes)
                ->where('name', $card->sourceCard->name)
                ->with(['cardMetadata', 'setData'])
                ->get();

            \Log::info("Found " . $printings->count() . " printings for {$card->sourceCard->name}");

            foreach ($printings as $printing) {
                $scryfallId = optional(json_decode($printing->cardMetadata->identifiers))->scryfallId;
                if (!$scryfallId) continue;

                \Log::info("Fetching price for card {$cardData->name} (set {$printing->set_code})");

                $priceData = MtgCardPriceFetcher::fetchPriceByScryfallId($scryfallId);
                if (!$priceData) continue;

                $records = [];

                foreach (['usd' => false, 'usd_foil' => true] as $currencyKey => $isFoil) {
                    $price = $priceData[$currencyKey] ?? null;
                    if (!$price) continue;

                    $existingPrice = MtgCardToSetPrice::where([
                        ['card_metadata_id', '=', $printing->cardMetadata->id],
                        ['card_data_id', '=', $card->id],
                        ['magic_set_data_id', '=', $printing->setData->id],
                        ['currency', '=', 'usd'],
                        ['is_foil', '=', $isFoil],
                        ['price_date', '=', $today],
                    ])->first();

                    if ($existingPrice) {
                        $records[] = $existingPrice;
                        continue;
                    }

                    DB::transaction(function () use ($printing, $card, $price, $isFoil, $today, &$records) {
                        MtgCardPrices::updateOrCreate([
                            'card_metadata_id' => $printing->cardMetadata->id,
                            'currency' => 'usd',
                            'is_foil' => $isFoil,
                            'price_date' => $today,
                        ], [
                            'price' => $price,
                            'source' => 'scryfall',
                        ]);

                        $record = MtgCardToSetPrice::create([
                            'card_metadata_id' => $printing->cardMetadata->id,
                            'card_data_id' => $card->id,
                            'magic_set_data_id' => $printing->setData->id,
                            'currency' => 'usd',
                            'price' => $price,
                            'is_foil' => $isFoil,
                            'price_date' => $today,
                            'source' => 'scryfall',
                        ]);

                        $records[] = $record;
                    });
                }

                if (!isset($results[$card->id])) {
                    $results[$card->id] = [];
                }

                $results[$card->id] = array_merge(
                    $results[$card->id],
                    $this->enrichResponse(collect($records), $card, $printing, $printing->cardMetadata)->toArray()
                );
            }
        }

        return response()->json($results);
    }

    private function enrichResponse($priceRecords, $normalizedCard, $sourceCard, $metadata)
    {
        return $priceRecords->map(function ($record) use ($normalizedCard, $sourceCard, $metadata) {
            return [
                'price' => $record->price,
                'is_foil' => $record->is_foil,
                'currency' => $record->currency,
                'source' => $record->source,
                'price_date' => $record->price_date,
                'normalized_card_id' => $normalizedCard->id,
                'card_data_id' => $sourceCard->id,
                'set_code' => $sourceCard->set_name,
                'scryfall_id' => $metadata->scryfallId ?? json_decode($metadata->identifiers)['scryfallId'] ?? null,
            ];
        });
    }
}
