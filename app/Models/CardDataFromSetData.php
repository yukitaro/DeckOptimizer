<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CardMetadata;
use App\Models\CardsInCollection;
use App\Models\CardsInDeck;
use App\Models\CollectedCardsFromSets;
use App\Models\DeckManagement;
use App\Models\MtgBulkPrices;
use App\Models\SetData;

class CardDataFromSetData extends Model
{
    /**
     * The table associated with the model
     */
    protected $table = 'card_data_from_set_data';

    public function setData()
    {
        return $this->belongsTo(SetData::class, 'magic_set_data_id');
    }

    public function cardMetadata() : HasOne
    {
        return $this->hasOne(CardMetadata::class, 'id', 'card_metadata_id');
    }

    public function collectedCards()
    {
        return $this->hasMany(CollectedCardsFromSets::class, 'card_data_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'set_name',
        'magic_set_data_id',
        'number_in_set',
        'card_uuid',
        'card_multiverse_id',
        'colors',
        'colorIdentities',
        'keywords',
        'mana_cost',
        'mana_value',
        'card_metadata_id',
        'power',
        'printings',
        'rarity',
        'release_date',
        'set_code',
        'text',
        'toughness',
        'type',
        'types',
        'image_url',
        'image_normalized_at',
        'slug'
    ];
}
