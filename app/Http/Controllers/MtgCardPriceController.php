<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CardDataFromSetData;
use App\Models\MtgCardPrice;
use App\Models\MtgCardToSetPrice;
use App\Models\CardDataNormalized;
use App\Services\MtgCardPriceFetcher;

class MtgCardPriceController extends Controller
{

public function fetchPrices(Request $request)
{
    \Log::info('Received card_data_normalized_ids:', $request->input('card_data_normalized_ids'));

    $cardDataIds = $request->input('card_data_normalized_ids', []);
    $today = now()->toDateString();
    $results = [];

    $normalizedCards = CardDataNormalized::with('sourcePrinting.cardMetadata', 'sourcePrinting.setData')
        ->whereIn('id', $cardDataIds)
        ->get();

    \Log::info('Received card_data_normalized_ids:', [
        'ids' => $request->input('card_data_normalized_ids')
    ]);


    foreach ($normalizedCards as $card) {
   $cardData = $card->sourcePrinting;
    if (!$cardData) {
        \Log::warning("No sourcePrinting for normalized ID {$card->id}");
        continue;
    }

    $meta = $cardData->cardMetadata;
    $set = $cardData->setData;

    if (!$meta) {
        \Log::warning("No cardMetadata for sourcePrinting ID {$cardData->id}");
        continue;
    }

    if (!$set) {
        \Log::warning("No setData for sourcePrinting ID {$cardData->id}");
        continue;
    }

    \Log::info("Resolved: normalized {$card->id} → printing {$cardData->id}, metadata {$meta->id}, set {$set->id}");
        if (!$meta || !$set) continue;

        $scryfallId = $meta->scryfallId ?? json_decode($meta->identifiers)['scryfallId'] ?? null;
        if (!$scryfallId) continue;

        // Check if set-specific price already exists
        $existingPrices = MtgCardToSetPrice::where([
            ['card_metadata_id', '=', $meta->id],
            ['card_data_id', '=', $card->id],
            ['magic_set_data_id', '=', $set->id],
            ['price_date', '=', $today],
        ])->get();

        \Log::info("Normalized ID {$normalized->id} → SourcePrinting: {$cardData->id}, Metadata: {$meta->id}, Set: {$set->id}");
        \Log::info("Scryfall ID for card {$meta->id}: {$scryfallId}");

        if ($existingPrices->count()) {
            $results[$card->id] = $existingPrices;
            continue;
        }

        // Fetch from Scryfall
        $priceData = MtgCardPriceFetcher::fetchPriceByScryfallId($scryfallId);
        if (!$priceData) continue;

        DB::transaction(function () use ($meta, $card, $set, $priceData, $today, &$results) {
            $records = [];

            foreach (['usd' => false, 'usd_foil' => true] as $currencyKey => $isFoil) {
                $price = $priceData[$currencyKey] ?? null;
                if (!$price) continue;

                // Global price
                MtgCardPrice::updateOrCreate([
                    'card_metadata_id' => $meta->id,
                    'currency' => 'usd',
                    'is_foil' => $isFoil,
                    'price_date' => $today,
                ], [
                    'price' => $price,
                    'source' => 'scryfall',
                ]);


                \Log::info("Stored price for card {$card->id}: {$price}");
                // Set-specific price
                $record = MtgCardToSetPrice::create([
                    'card_metadata_id' => $meta->id,
                    'card_data_id' => $card->id,
                    'magic_set_data_id' => $set->id,
                    'currency' => 'usd',
                    'price' => $price,
                    'is_foil' => $isFoil,
                    'price_date' => $today,
                    'source' => 'scryfall',
                ]);

                $records[] = $record;
            }

            $results[$card->id] = collect($records);
        });
    }

    return response()->json($results);
}
}
