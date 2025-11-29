<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\InviteRegistrationController;
use App\Http\Controllers\CardDataController;
use App\Http\Controllers\DeckController;

use App\Models\CardData;
use App\Models\CardDataFromSetData;
use App\Models\CardsInDeck;
use App\Models\CollectionManagement;
use App\Models\DeckManagement;
use App\Models\SetData;

use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cards/{num_cards}', [CardDataController::class, 'retrieve']);

Route::get('/sets/{cardminimum?}', function (int $cardminimum = 85) {
    return SetData::where('total_cards', '>', $cardminimum)
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
    $retrieveRecent = request('retrieveRecent') || null;

    if ($retrieveRecent) {
        return DeckManagement::orderBy('created_at', 'desc')
            ->limit($retrieveRecent)
            ->get();
    } else {
        return DeckManagement::limit($limit)
            ->get();
    }
});

use Illuminate\Http\Request;

Route::post('/csrf-check', function (Request $request) {
    return response()->json([
        'message' => 'CSRF token validated successfully.',
        'session_id' => session()->getId(),
        'user' => auth()->user(),
    ]);
});

Route::get('/register', [InviteRegistrationController::class, 'showForm']);
Route::post('/register', [InviteRegistrationController::class, 'register']);