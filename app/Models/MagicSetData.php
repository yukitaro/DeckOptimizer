<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagicSetData extends Model
{
    /**
     * The table associated with the model
     */
    protected $table = 'magic_sets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'set_name',
        'official_set_id',
        'published_year'
    ];
}
