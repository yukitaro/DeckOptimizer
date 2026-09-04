<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardData extends Model
{
    /**
     * The table associated with the model
     */
    protected $table = 'card_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'set_name',
        'official_set_id',
        'card_id',
        'card_uuid',
        'colors',
        'colorIdentities',
        'keywords',
        'mana_cost',
        'mana_value',
        'power',
        'printings',
        'rarity',
        'text',
        'toughness',
        'type',
        'types',
        'image_url'
    ];
}
