<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CardDataController;
use App\Http\Controllers\DeckController;

use App\Models\CardData;
use App\Models\CardDataFromSetData;
use App\Models\CardsInDeck;
use App\Models\Collections;
use App\Models\DeckManagement;
use App\Models\SetData;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/cards/{num_cards}', [CardDataController::class, 'retrieve']);

/*Route::get('/cardsJSON/{num_cards}', function (int $num_cards) {
    $matching_cards = CardData::where('rarity', 'common')
    ->limit($num_cards)
    ->get();

    return $matching_cards;
});*/

Route::get('/sets/{cardminimum?}', function (int $cardminimum = 85) {
    return SetData::where('total_cards', '>', $cardminimum)
    ->get();
});

Route::get('/cards/name/{name}/rarities/{rarities?}', function (string $name, string $rarities) {

    $limit = request('limit');

    if ($rarities === '') {
        $rarities = "common,uncommon,rare,mythic";
    }

    //return CardData::whereIn('rarity', explode(',', $rarities))
    return CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
    ->where('name', 'LIKE', "%{$name}%")
    ->limit($limit)
    ->get();
});

Route::get('/cardsfromsets/name/{name}/rarities/{rarities?}', function (string $name, string $rarities) {

    $limit = request('limit');

    if ($rarities === '') {
        $rarities = "common,uncommon,rare,mythic";
    }

    return CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
    ->where('name', 'LIKE', "%{$name}%")
    ->limit($limit)
    ->get();
});

Route::get('/cardsfromsets/{setnames}/{rarities?}', function (string $setnames, string $rarities = '') {

    $limit = request('limit');
    $colorFilters = request('colorFilters');

    if ($rarities === '') {
        $rarities = "common,uncommon,rare,mythic";
    }

    $query = CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
        ->where('set_name', '=', "$setnames")
        ->whereRaw('number_in_set REGEXP ?', ['^[0-9]+$'])
        ->whereNotNull('card_multiverse_id');

    // Only apply color filtering if colorFilters is provided and not empty
    if ($colorFilters && $colorFilters !== '') {
        $query->whereIn('colors', explode(',', $colorFilters));
    }

    return $query->limit($limit)->get();
});

Route::get('/cardsJSON/{num_cards}/{colors?}', function (int $num_cards, string $colors = 'W') {

    $colorInDb = "";

    switch ($colors) {
        case 'islands':
            $colorInDb = 'U';
            break;
        case 'plains':
            $colorInDb = 'W';
            break;
        case 'swamps':
            $colorInDb = 'B';
            break;
        case 'mountains':
            $colorInDb = 'R';
            break;
        case 'forests':
            $colorInDb = 'G';
            break;

    }

    $matching_cards = CardData::where('rarity', 'common')
    ->where('colors', $colorInDb)
    ->limit($num_cards)
    ->get();

    return $matching_cards;
});


Route::post('/deck', [DeckController::class, 'store']);

/* Route::get('/cardsInDeck/{deck_id}', function ($deck_id) {
        $deck = DeckManagement::with('cardsInDeck')->find($deck_id);

    if (!$deck) {
        return response()->json(['error' => 'Deck not found'], 404);
    }

    return response()->json($deck->cardsInDeck);
});
 */
Route::get('/decks', function () {
    $limit = request('limit');

    return DeckManagement::limit($limit)
        ->get();
});

Route::get('/collections', function () {
    return Collections::get();
});

use Illuminate\Http\Request;

Route::post('/csrf-check', function (Request $request) {
    return response()->json([
        'message' => 'CSRF token validated successfully.',
        'session_id' => session()->getId(),
        'user' => auth()->user(),
    ]);
});
