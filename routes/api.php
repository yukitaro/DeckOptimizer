<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\CardsInDeck;
use App\Models\DeckManagement;

Route::get('/cardsInDeck/{deck_id}', function ($deck_id) {
    $deck = DeckManagement::findOrFail($deck_id);

    // Step 1: Get all CardsInDeck for this deck
    $cardsInDeck = CardsInDeck::where('deck_management_id', $deck_id)->get();

    // Step 2: Build a map of card_data_normalized_id → card_count
    $countMap = $cardsInDeck->groupBy('card_data_normalized_id')->map(function ($group) {
        return $group->sum('card_count');
    });

    dd($countMap);

    // Step 3: Get normalized cards via hasManyThrough
    $normalizedCards = $deck->normalizedCards;
    dd($deck->normalizedCards);

    // Step 4: Attach card_count to each normalized card
    $enriched = $normalizedCards->map(function ($card) use ($countMap) {
        $normalizedCards->pluck('id');

        return [
            'id' => $card->id,
            'name' => $card->name,
            'type' => $card->type,
            'mana_cost' => $card->mana_cost,
            'image_url_to_use' => $card->image_url_to_use,
            'card_count' => $countMap[$card->id] ?? 0
        ];
    });

    return response()->json($enriched);
});
// ->middleware('auth:sanctum');


Route::get('/decks', function () {
    $limit = request('limit');

    return DeckManagement::limit($limit)
        ->get();
});
