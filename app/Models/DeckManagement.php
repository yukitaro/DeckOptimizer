<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\Visibility;
use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\MtgArchetype;
use App\Models\MtgDeckBoardGroups;

use Laravel\Sanctum\HasApiTokens;

class DeckManagement extends Model
{
    protected $table = 'deck_management';

    public function cardsInDeck(): HasMany
    {
        return $this->hasMany(CardsInDeck::class, 'deck_management_id');
    }

    public function boardGroups(): HasMany
    {
        return $this->hasMany(MtgDeckBoardGroups::class, 'deck_id');
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

    public function archetypeModel(): BelongsTo
    {
        return $this->belongsTo(MtgArchetype::class, 'archetype_id');
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
        'format',
        'archetype_id',
        'visibility',
    ];

    protected $casts = [
        'visibility' => Visibility::class,
    ];
}
