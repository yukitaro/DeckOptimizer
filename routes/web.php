<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CardDataController;
use App\Models\CardData;

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

Route::get('/cardsJSON/{num_cards}/{colors?}', function (int $num_cards, string $colors = 'W') {

    $colorInDb = "";

    switch ($colors) {
        case 'islands':
            $colorInDb = '[\'U\']';
            break;
        case 'plains':
            $colorInDb = '[\'W\']';
            break;
        case 'swamps':
            $colorInDb = '[\'B\']';
            break;
        case 'mountains':
            $colorInDb = '[\'R\']';
            break;
        case 'forests':
            $colorInDb = '[\'G\']';
            break;

    }

    $matching_cards = CardData::where('rarity', 'common')
    ->where('colors', $colorInDb)
    ->limit($num_cards)
    ->get();

    return $matching_cards;
});