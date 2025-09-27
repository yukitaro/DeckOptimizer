<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\CardDataFromSetData;
use App\Models\SetsInCollection;

class CollectedCardsFromSets extends Model
{
    protected $table = 'collected_cards';

    public function collectedFromSet() : HasOne
    {
        return $this->hasOne(SetsInCollection::class, 'collected_cards_from_sets_id');
    }

    public function cardFromSet() : BelongsTo
    {
        return $this->belongsTo(CardDataFromSetData::class, 'card_data_from_set_data_id');
    }

    protected $fillable = [
        'card_count',
        'set_in_collection_id',
        'card_data_id',
        'is_foil',
        'condition',
        'printing_variant',
        'purchase_price',
        'storage_location'
    ];
}
