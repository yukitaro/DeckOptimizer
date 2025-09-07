<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CardsInDeck extends Model
{
    protected $table = 'cards_in_deck';

    public function belongsTo() : BelongsTo
    {
        return $this->belongsTo(DeckManagement::class);
    }

    public function normalizedCard(): HasOne
    {
        return $this->hasOne(CardDataNormalized::class);
    }
}
