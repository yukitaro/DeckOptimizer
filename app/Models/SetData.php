<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CardDataFromSetData;

class SetData extends Model
{
    /**
     * The table associated with the model
     */
    protected $table = 'magic_set_data';

    public function cardsInSet()
    {
        return $this->hasMany(CardDataFromSetData::class, 'magic_set_data_id');
    }    

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'set_name',
        'official_set_code',
        'release_date',
        'total_cards',
        'set_code',
        'card_metadata_id',
        'imported_from_mtgjson',
        'date_of_json_used_for_import'
    ];
}
