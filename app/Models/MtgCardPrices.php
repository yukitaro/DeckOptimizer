<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgCardPrices extends Model
{
    protected $table = 'mtg_card_prices';

    protected $fillable = [
        'card_metadata_id',
        'currency',
        'price',
        'is_foil',
        'price_date',
        'source'
    ];
}
