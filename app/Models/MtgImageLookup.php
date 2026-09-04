<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgImageLookup extends Model
{
    protected $table = 'mtg_image_lookups';

    protected $casts = [
        'scryfall_image_uris' => 'array',
    ];


    protected $fillable = [
        'card_uuid',
        'original_image_url',
        'canonical_image_url',
        'canonical_image_url_back',
        'scryfall_image_uris'
    ];
}
