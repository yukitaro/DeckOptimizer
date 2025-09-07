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
        //$requestString = implode(", ", $request->all());
        //echo 'Complete request: ' . $requestString . '\n';
/*         echo 'Got this request to create deck with name: ' . $request->input('deckName') . '\n';
        echo('With these cards: ' . $request->input('deckName') . '\n');


        $owner_login = 'yukitaro';

        $owner_data = DeckOwner::where('owner_login', $owner_login)
        ->get();

        if ($owner_data->isEmpty()) {
            $owner_data = DeckOwner::create([
                'owner_login' => $owner_login,
                'free_text' => 'haha these are all mine'
            ]);
        } */

        // deckData: deckSomething.value,

        $cardLinesToParse = preg_split('/\R/', $request->input('deckData'));

        foreach ($cardLinesToParse as $cardLine) {
            $pattern = "/(\d){1,2}\s{1}(.*)/";
            preg_match($pattern, $cardLine, $matches);

            $matchingCard = CardDataNormalized::where('name', $matches[2])
                ->get();

            if ($matchingCard->isEmpty()) {
                echo "Bummer, couldn't find a match for: " . $matches[2] . "\n";
            } else {
                echo "Found a matching card: " . $matchingCard[0]->name . "\n";
            }
        }

/*         DB::transaction(function use ($request)) {
            $managed_deck = DeckManagement::create([
                'deck_name' => $request->input('deckName'),
                'description' => $request->input('deckDescription'),
                'external_link' => $request->input('deckLink'),
            ]);

            }
         */
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
