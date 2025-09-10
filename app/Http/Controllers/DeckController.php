<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\DeckManagement;
use App\Models\DeckOwner;

class DeckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $errors = [];
        $matchesFound = [];
        $index = 0;
        $cardCountInDeck = 0;

        $cardLinesToParse = preg_split('/\R/', $request->input('deckData'));

        $owner_login = 'yukitaro';

        $owner_data = DeckOwner::firstOrCreate(
            ['owner_login' => $owner_login],
            ['free_text' => 'haha these are all mine', 'deck_id' => 1]
        );

        
        $owner_data->save();
       
        $importedDeck = $owner_data->ownedDecks()->create([
            'deck_name' => $request['deckName'],
            'description' => $request['deckDescription'],
            'external_link' => $request['deckLink'],
            'num_cards' => $request['num_cards'] ?? 0
        ]);

        $importedDeck->save();

        foreach ($cardLinesToParse as $cardLine) {
            $pattern = "/(\d){1,2}\s{1}(.*)/";
            preg_match($pattern, $cardLine, $matches);

            if (!isset($matches[2])) {
                $errors[] = [
                    'line' => $index + 1,
                    'input' => $cardLine,
                    'error' => 'Line format invalid or incomplete'
                ];
                continue;
            }

            $matchingCard = CardDataNormalized::where('name', $matches[2])
                ->first();

            if (!$matchingCard) {
                $errors[] = [
                    'line' => $index + 1,
                    'input' => $matches[2],
                    'error' => 'No matching card found for: ' . $matches[2]
                ];
            } else {
                $cardCountInDeck += $matches[1];
                $matchesFound[] = $matchingCard->name;
                $importedDeck->cardsInDeck()->create([
                    'card_name' => $matches[2],
                    'card_count' => $matches[1],
                    'image_url' => $matchingCard['image_url_to_use'],
                    'card_data_normalized_id' => $matchingCard->id
                ]);
                $importedDeck->save();
            }
        }

        return response()->json([
            'status' => 'completed',
            'matched_cards' => $matchesFound,
            'errors' => $errors,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
