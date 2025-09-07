<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeckManagement extends Model
{
    protected $table = 'deck_management';

    public function belongsTo() : BelongsTo
    {
        return $this->belongsTo(DeckOwner::class);
    }

    public function cardsInDeck(): HasMany
    {
        return $this->hasMany(CardsInDeck::class);
    }
}
