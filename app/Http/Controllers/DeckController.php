<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\DTOs\DeckImportDTO;
use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\DeckManagement;
use App\Models\MtgArchetype;
use App\Models\User;

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

        $owner_data = auth()->user();
               
        $importedDeck = $owner_data->ownedDecks()->create([
            'deck_name' => $request['deckName'],
            'description' => $request['deckDescription'],
            'external_link' => $request['deckLink'],
            'num_cards' => $request['num_cards'] ?? 0,
            'archetype' => $request['deckArchetype'] ?? '',
            'format' => $request['deckFormat'] ?? 'pauper',
            'archetype_id' => $request['archetypeId'] ?? null,
            'visibility' => strtolower($request['deckVisibility'] ?? 'public'),
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
                if (in_array(trim($cardLine), ['Deck', 'Decklist', ''])) {
                    $index++;
                    continue;
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
                    continue;
                }

                $errors[] = [
                    'line' => $index + 1,
                    'input' => $cardLine,
                    'error' => 'Line format invalid or incomplete'
                ];
                $index++;
                continue;
            }

            $cardName = trim($matches[2]);
            $matchingCard = CardDataNormalized::whereRaw('LOWER(TRIM(normalized_name)) = ?', [strtolower($cardName)])->first();

            // Fallback: try to match front face of split cards
            if (!$matchingCard) {
                $similarCards = CardDataNormalized::where('normalized_name', 'LIKE', '%' . $cardName . '%')->get();

                foreach ($similarCards as $similarCard) {
                    if (str_contains($similarCard->normalized_name, '//')) {
                        $frontName = explode('//', $similarCard->normalized_name)[0];
                        if (strtolower(trim($frontName)) === strtolower($cardName)) {
                            $matchingCard = $similarCard;
                            break;
                        }
                    }
                }
            }

            if ($matchingCard) {
                $current_total_cards += (int) $matches[1];
                $matchesFound[] = $matchingCard->normalized_name;

                $currentBoardGroup->cardsInGroup()->create([
                    'card_name' => $matchingCard->normalized_name,
                    'card_count' => $matches[1],
                    'image_url' => $matchingCard->image_url_to_use,
                    'card_data_normalized_id' => $matchingCard->id,
                    'deck_management_id' => $importedDeck->id,
                ]);
            } else {
                $errors[] = [
                    'line' => $index + 1,
                    'input' => $cardName,
                    'error' => 'No matching card found for: ' . $cardName
                ];
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

    // I'm not sure what I made this for. 
    public function storeFromDTO(DeckImportDTO $dto, User $owner)
    {
        $deck = $owner->ownedDecks()->create([
            'deck_name' => $dto->name,
            'description' => $dto->description,
            'external_link' => $dto->sourceUrl,
            'num_cards' => 0, // will be updated later
            'archetype' => $dto->archetype ?? '',
            'format' => $dto->format ?? 'pauper'
        ]);

        $mainboardGroup = $deck->boardGroups()->create([
            'board_type' => 'main',
            'label' => 'Main Deck'
        ]);

        $sideboardGroup = $deck->boardGroups()->create([
            'board_type' => 'side',
            'label' => 'Sideboard'
        ]);

        $totalMain = $this->hydrateCards($dto->mainboard, $mainboardGroup, $deck);
        $totalSide = $this->hydrateCards($dto->sideboard, $sideboardGroup, $deck);

        $mainboardGroup->num_cards = $totalMain;
        $sideboardGroup->num_cards = $totalSide;
        $deck->num_cards = $totalMain + $totalSide;

        $mainboardGroup->save();
        $sideboardGroup->save();
        $deck->save();

        return $deck;
    }

    private function hydrateCards(array $cards, $group, $deck): int {
        $total = 0;

        foreach ($cards as $card) {
            $match = CardDataNormalized::whereRaw('LOWER(TRIM(normalized_name)) = ?', [strtolower(trim($card['name']))])->first();

            if ($match) {
                $group->cardsInGroup()->create([
                    'card_name' => $match->normalized_name,
                    'card_count' => $card['count'],
                    'image_url' => $match->image_url_to_use,
                    'card_data_normalized_id' => $match->id,
                    'deck_management_id' => $deck->id,
                ]);
                $total += $card['count'];
            }
        }

        return $total;
    }    

    public function knownArchetypes(): Collection
    {
        return MtgArchetype::orderBY('name', 'ASC')
            ->get(['name']);
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

        $authid = auth()->id();
        \Log::info('Attempting to delete deck ID: ' . $id . ' owned by user ID: ' . $deck->deck_owner_id . ' authid is ' . $authid);
        if ($deck->deck_owner_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete related cards
        $deck->cardsInDeck()->delete();

        // Delete owner record
        //$deck->deckOwner()->delete();

        // Delete the deck itself
        $deck->delete();

        return response()->json(['message' => 'Deck deleted successfully']);
    }
}
