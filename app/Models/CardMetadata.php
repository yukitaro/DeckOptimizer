<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

use App\Casts\JsonToArray;
use App\Models\CardDataFromSetData;

class CardMetadata extends Model
{
    public function cardData() : BelongsTo
    {
        return $this->belongsTo(CardDataFromSetData::class, 'card_data_from_set_data_id');
    }

    protected $casts = [
        'purchaseUrls' => JsonToArray::class,
        'identifiers' => 'array',        
        'normalized_attributes' => 'array',
    ];

    protected $fillable = [
        'cardKingdomId',
        'multiverseId',
        'scryfallId',
        'tcgplayerProductId',
        'tcgplayerPurchaseUrl',
        'card_data_from_set_data_id'
    ];
}
