<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\CardDataFromSetData;
use App\Models\CollectedCardsFromSets;
use App\Models\SetsInCollection;

class CollectedCardsFromSets extends Model
{
    protected $table = 'collected_cards';

    public function setInCollection() : BelongsTo
    {
        return $this->belongsTo(SetsInCollection::class, 'set_in_collection_id');
    }

    public function cardFromSet() : BelongsTo
    {
        return $this->belongsTo(CardDataFromSetData::class, 'card_data_id');
    }    

    protected $fillable = [
        'card_count',
        'set_in_collection_id',
        'card_data_id',
    ];
}
