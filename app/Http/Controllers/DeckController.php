<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $current_total_cards = 0;
        $total_cards_mainboard = 0;
        $total_cards_sideboard = 0;
        $importedDeckSideboard = null;

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
            'num_cards' => $request['num_cards'] ?? 0,
            'archetype' => $request['deckArchetype'] ?? '',
            'format' => $request['deckFormat'] ?? 'pauper'
        ]);

        $importedDeck->save();

        $importedDeckMainboard = $importedDeck->boardGroups()->create([
            'board_type' => 'main',
            'label' => 'Main Deck'
        ]);

        $currentBoardGroup = $importedDeckMainboard;
        $importedDeckMainboard->save();

        foreach ($cardLinesToParse as $cardLine) {
            $pattern = "/(\d+)\s+(.*)/";
            preg_match($pattern, $cardLine, $matches);

            if (!isset($matches[2])) {
                if ($cardLine === 'Deck' || $cardLine === 'Decklist') {
                    $index++;
                    continue; // skip header lines
                }
                
                if (trim($cardLine) === '') {
                    $index++;
                    continue; // skip empty lines
                }

                if (stripos($cardLine, 'sideboard') !== false) {
                    $index++;

                    $importedDeckMainboard->num_cards = $current_total_cards;
                    $total_cards_mainboard = $current_total_cards;
                    $importedDeckMainboard->save();

                    $currentBoardGroup = $importedDeck->boardGroups()->create([
                        'board_type' => 'side',
                        'label' => 'Sideboard'
                    ]);

                    $importedDeckSideboard = $currentBoardGroup;

                    $current_total_cards = 0;
                    continue; // skip sideboard lines for now
                }

                $errors[] = [
                    'line' => $index + 1,
                    'input' => $cardLine,
                    'error' => 'Line format invalid or incomplete'
                ];
                continue;
            }

            $matchingCard = CardDataNormalized::whereRaw('LOWER(TRIM(normalized_name)) = ?', [strtolower(trim($matches[2]))])
                ->first();

            if (!$matchingCard) {
                $errors[] = [
                    'line' => $index + 1,
                    'input' => $matches[2],
                    'error' => 'No matching card found for: ' . $matches[2]
                ];
                //Log::warning("Unresolved card during import: '{$matches[2]}' on line {$index + 1}");
            } else {
                $current_total_cards += (int) $matches[1];
                $matchesFound[] = $matchingCard->normalized_name;

                $currentBoardGroup->cardsInGroup()->create([
                    'card_name' => $matchingCard->normalized_name,
                    'card_count' => $matches[1],
                    'image_url' => $matchingCard->image_url_to_use,
                    'card_data_normalized_id' => $matchingCard->id,
                    'deck_management_id' => $importedDeck->id,
                ]);
            }

            $index++;
        }

        if ($importedDeckSideboard) {
            $total_cards_sideboard = $current_total_cards;
            $importedDeckSideboard->num_cards = $total_cards_sideboard;
            $importedDeckSideboard->save();
        }

        $importedDeck->num_cards = $total_cards_mainboard + $total_cards_sideboard;
        $importedDeck->save();

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
    public function destroy($id)
    {
        $deck = DeckManagement::findOrFail($id);

        // Delete related cards
        $deck->cardsInDeck()->delete();

        // Delete owner record
        $deck->deckOwner()->delete();

        // Delete the deck itself
        $deck->delete();

        return response()->json(['message' => 'Deck deleted successfully']);
    }
}
