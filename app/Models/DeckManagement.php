<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use Laravel\Sanctum\HasApiTokens;

class DeckManagement extends Model
{
    protected $table = 'deck_management';

    public function deckOwner() : BelongsTo
    {
        return $this->belongsTo(DeckOwner::class);
    }

    public function cardsInDeck(): HasMany
    {
        return $this->hasMany(CardsInDeck::class, 'deck_management_id');
    }

    public function normalizedCards(): HasManyThrough
    {
        return $this->hasManyThrough(
            CardDataNormalized::class,     // Final model
            CardsInDeck::class,            // Intermediate model
            'deck_management_id',          // Foreign key on CardsInDeck
            'id',                          // Foreign key on CardDataNormalized
            'id',                          // Local key on DeckManagement
            'card_data_normalized_id'      // Local key on CardsInDeck
        );
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'deck_owner_id',
        'deck_name',
        'description',
        'external_link',
        'num_cards',
        'archetype',
        'format'
    ];    
}
