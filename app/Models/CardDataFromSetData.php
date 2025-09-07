<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
