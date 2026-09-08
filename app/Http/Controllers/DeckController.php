<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\DataTransferObjects\DeckImportDTO;
use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\DeckManagement;
use App\Models\MtgArchetype;
use App\Models\MtgImageLookup;
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
    public function storeFromDTO(Request $request)
    {
        $owner_data = auth()->user();
        $dto = DeckImportDTO::fromRequest($request);
        $deck = $owner_data->ownedDecks()->create([
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
            $cardName = '';
            $cardCount = 1;

            if (is_string($card)) {
                // Parses "4 Bramble Wurm" into count = 4, name = "Bramble Wurm"
                if (preg_match('/^\s*(\d+)\s+(.+)$/', trim($card), $matches)) {
                    $cardCount = (int)$matches[1];
                    $cardName = trim($matches[2]);
                } else {
                    $cardName = trim($card);
                }
            } elseif (is_array($card)) {
                // Fallback if the array already contains structured keys ['name' => ..., 'count' => ...]
                $cardName = $card['name'] ?? '';
                $cardCount = (int)($card['count'] ?? 1);
            }

            if (empty($cardName)) {
                continue;
            }

            $match = CardDataNormalized::whereRaw('LOWER(TRIM(normalized_name)) = ?', [strtolower($cardName)])->first();

            if ($match) {
                $group->cardsInGroup()->create([
                    'card_name' => $match->normalized_name,
                    'card_count' => $cardCount,
                    'image_url' => $match->image_url_to_use,
                    'card_data_normalized_id' => $match->id,
                    'deck_management_id' => $deck->id,
                ]);
                $total += $cardCount;
            }
        }

        return $total;
    }    

    public function getPauperStaples(array $archetypes = []): array
    {
        // 1. Deep Eager Load through DeckManagement -> CardsInDeck -> NormalizedCard -> SourceCard -> Metadata
        $query = DeckManagement::query()
            ->where('format', 'pauper')
            ->with([
                'cardsInDeck.normalizedCard.sourceCard.cardMetadata',
                'cardsInDeck.normalizedCard.sourceCard.setData',
            ]);

        if (!empty($archetypes)) {
            $query->where(function ($q) use ($archetypes) {
                foreach ($archetypes as $archetype) {
                    $q->orWhere('archetype', 'LIKE', '%' . trim($archetype) . '%');
                }
            });
        }

        $targetDecks = $query->get();
        $totalDecksCount = $targetDecks->count();

        if ($totalDecksCount === 0) {
            return ['pauper_staples' => []];
        }

        // 2. Pre-fetch Image Overrides in ONE Batch Query (Fixes N+1)
        $cardUuids = $targetDecks->pluck('cardsInDeck.*.normalizedCard.sourceCard.card_uuid')
            ->flatten()
            ->filter()
            ->unique();

        $imageLookups = MtgImageLookup::whereIn('card_uuid', $cardUuids)
            ->pluck('canonical_image_url', 'card_uuid'); // Keyed by card_uuid for O(1) lookup

        $rawStaples = [];

        // 3. Aggregate cards and extract rich display metadata
        foreach ($targetDecks as $deck) {
            foreach ($deck->cardsInDeck as $cardInDeck) {
                $normCard = $cardInDeck->normalizedCard;
                if (!$normCard) continue;

                $cardId = $normCard->id;

                if (!isset($rawStaples[$cardId])) {
                    $sourceCard = $normCard->sourceCard;
                    $meta       = $sourceCard?->cardMetadata;
                    $uuid       = $sourceCard?->card_uuid;

                    // Priority Fallback: ImageLookup -> Metadata -> Normalized default
                    $imageUrl = $imageLookups[$uuid] 
                        ?? $meta?->image_url_to_use 
                        ?? $normCard->image_url_to_use;

                    $rawStaples[$cardId] = [
                        'card_data_normalized_id' => $cardId,
                        'name'                    => $sourceCard?->name ?? $normCard->normalized_name,
                        'normalized_name'         => $normCard->normalized_name,
                        'image_url_to_use'        => $imageUrl,
                        'mana_cost'               => $sourceCard?->mana_cost,
                        'type'                    => $sourceCard?->type,
                        'scryfall_id'             => $meta?->scryfallId,
                        'slug'                    => $sourceCard?->slug,
                        'appearances'             => 0,
                        'absolute_count'          => 0,
                    ];
                }

                $rawStaples[$cardId]['appearances']++;
                $rawStaples[$cardId]['absolute_count'] += $cardInDeck->card_count;
            }
        }

        // 4. Calculate final metrics
        $pauperStaples = [];

        foreach ($rawStaples as $stats) {
            $appearances = $stats['appearances'];
            $absCount    = $stats['absolute_count'];

            // Remove temporary working keys before sending JSON
            unset($stats['appearances'], $stats['absolute_count']);

            $pauperStaples[] = array_merge($stats, [
                'total_count'          => $absCount,
                'number_of_decks'      => $appearances,
                'average_num_in_decks' => round($absCount / $appearances, 2),
                'deck_inclusion_rate'  => round(($appearances / $totalDecksCount) * 100, 1),
            ]);
        }

        // 5. Sort staples by popularity
        usort($pauperStaples, fn($a, $b) => $b['number_of_decks'] <=> $a['number_of_decks']);

        return [
            'analyzed_decks_count' => $totalDecksCount,
            'pauper_staples'       => $pauperStaples,
        ];
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
