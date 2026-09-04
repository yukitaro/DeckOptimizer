<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgCardToSetPrice extends Model
{
    protected $table = 'mtg_card_to_set_prices';

    public function cardMetadata()
    {
        return $this->belongsTo(CardMetadata::class, 'card_metadata_id');
    }

    protected $fillable = [
        'card_metadata_id',
        'card_data_id',
        'magic_set_data_id',
        'currency',
        'price',
        'is_foil',
        'price_date',
        'source'
    ];
}
