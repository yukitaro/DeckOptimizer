<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgBulkPrices extends Model
{
    protected $table = 'mtg_bulk_prices';

    protected $primaryKey = 'scryfall_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'scryfall_id',
        'oracle_id',
        'name',
        'set_name',
        'collector_number',
        'usd',
        'usd_foil',
        'rarity',
        'released_at',
        'image_uri',
        'price_date',
    ];
}