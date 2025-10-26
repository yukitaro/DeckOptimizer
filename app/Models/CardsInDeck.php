<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CardsInDeck extends Model
{
    protected $table = 'cards_in_deck';

    public function deckManagedBy() : BelongsTo
    {
        return $this->belongsTo(DeckManagement::class, 'deck_management_id');
    }

    public function normalizedCard(): BelongsTo
    {
        return $this->belongsTo(CardDataNormalized::class, 'card_data_normalized_id');
    }

    public function boardGroup(): BelongsTo
    {
        return $this->belongsTo(MtgDeckBoardGroups::class, 'mtg_deck_board_group_id', 'id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'card_name',
        'card_count',
        'image_url',
        'deck_management_id',
        'mtg_deck_board_group_id',
        'card_data_normalized_id',
        'updated_at'
    ];    
}
