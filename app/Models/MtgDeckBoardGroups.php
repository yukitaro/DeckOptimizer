<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\DeckManagement;

class MtgDeckBoardGroups extends Model
{
    protected $table = 'mtg_deck_board_groups';

    public function deck() : BelongsTo
    {
        return $this->belongsTo(DeckManagement::class, 'deck_id');
    }

    public function cardsInGroup() : HasMany
    {
        return $this->hasMany(CardsInDeck::class, 'mtg_deck_board_group_id');
    }

    protected $fillable = [
        'deck_id',
        'board_type', // e.g., 'main', 'side', 'commander'
        'label',       // e.g., 'Main Deck', 'Sideboard', 'Commander'
        'num_cards'   // number of cards in this group
    ];
}
