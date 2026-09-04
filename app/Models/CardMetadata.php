<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Models\MtgBulkPrices;

use App\Casts\JsonToArray;
use App\Models\CardDataFromSetData;

class CardMetadata extends Model
{
    public function cardData() : BelongsTo
    {
        return $this->belongsTo(CardDataFromSetData::class, 'card_data_from_set_data_id');
    }

    public function prices()
    {
        return $this->hasOne(MtgBulkPrices::class, 'scryfall_id', 'scryfallId');
    }

    public function getFinishesAttribute(): array
    {
        return $this->normalized_attributes['finishes'] ?? [];
    }

    public function hasFoil(): bool
    {
        return in_array('foil', $this->finishes, true);
    }

    public function hasNonfoil(): bool
    {
        return in_array('nonfoil', $this->finishes, true);
    }

    public function isFoilOnly(): bool
    {
        return $this->finishes === ['foil'];
    }

    public function isNonfoilOnly(): bool
    {
        return $this->finishes === ['nonfoil'];
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
