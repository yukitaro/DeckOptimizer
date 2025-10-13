<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\CardsInDeck;
use App\Models\CardDataNormalized;
use App\Models\DeckManagement;
use App\Models\MtgImageLookup;


class RetrieveCardsByBoardGroup extends Controller
{
    public function getCardsByBoardGroup($deck_id, $board_groups)
    {
        $deck = DeckManagement::findOrFail($deck_id);

        $boardgroups = explode(',', $board_groups);

        foreach ($boardgroups as $bg) {
            // Step 1: Get CardsInDeck filtered by board group
            $cardsInGroup = CardsInDeck::where('deck_management_id', $deck_id)
                ->whereHas('boardGroup', function ($q) use ($bg) {
                    $q->where('board_type', $bg);
                })
                ->with('boardGroup', 'normalizedCard.sourceCard.cardMetadata', 'normalizedCard.sourceCard.setData')
                ->get();

            // Step 2: Enrich each card directly from CardsInDeck
            $enriched = $cardsInGroup->map(function ($cardInDeck) {
                $card = $cardInDeck->normalizedCard;
                if (!$card) {
                    \Log::warning("No normalizedCard for CardsInDeck ID {$cardInDeck->id}");
                    return null;
                }

                $sourceCard = $card->sourceCard;
                if (!$sourceCard) {
                    \Log::warning("No sourceCard for normalized ID {$card->id}");
                    return null;
                }

                $meta = $sourceCard->cardMetadata;
                if (!$meta) {
                    \Log::warning("No cardMetadata for sourceCard ID {$sourceCard->id}");
                    return null;
                }

                $set = $sourceCard->setData;
                if (!$set) {
                    \Log::warning("No setData for sourceCard ID {$sourceCard->id}");
                    return null;
                }

                \Log::info("Resolved: normalized {$card->id} → sourceCard {$sourceCard->id}, metadata {$meta->id}, set {$set->id}");

                $imageLookup = MtgImageLookup::where('card_uuid', $sourceCard->card_uuid)->first();
                $imageUrl = $imageLookup->canonical_image_url
                    ?? $meta->image_url_to_use
                    ?? $card->image_url_to_use;

                return [
                    'id' => $card->id,
                    'image_url_to_use' => $imageUrl,
                    'has_mana_cost' => !is_null($sourceCard->mana_cost),
                    'name' => $sourceCard->name,
                    'normalized_name' => $card->normalized_name,
                    'scryfall_id' => $meta->scryfallId ?? null,
                    'type' => $sourceCard->type,
                    'card_count' => $cardInDeck->card_count,
                ];
            });

            $data[$bg] = $enriched->filter()->values(); // Remove nulls and reindex
        }

        return response()->json($data);
    }
}
