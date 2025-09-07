<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CardDataController;
use App\Http\Controllers\DeckController;
use App\Models\CardData;
use App\Models\CardDataFromSetData;

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

Route::get('/cards/name/{name}/rarities/{rarities?}', function (string $name, string $rarities) {

    $limit = request('limit');

    if ($rarities === '') {
        $rarities = "common, uncommon, rare, mythic";
    }

    return CardData::whereIn('rarity', explode(',', $rarities))
    ->where('name', 'LIKE', "%{$name}%")
    ->limit($limit)
    ->get();
});

Route::get('/cardsfromsets/name/{name}/rarities/{rarities?}', function (string $name, string $rarities) {

    $limit = request('limit');

    if ($rarities === '') {
        $rarities = "common, uncommon, rare, mythic";
    }

    return CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
    ->where('name', 'LIKE', "%{$name}%")
    ->limit($limit)
    ->get();
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

use Illuminate\Http\Request;

 

Route::get('/token', function (Request $request) {
    $token = $request->session()->token();
    $token = csrf_token();

    return $token;
});