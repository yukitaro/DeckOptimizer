<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collections extends Model
{
    public function setsInCollection()
    {
        return $this->hasMany(SetsInCollection::class);
    }

    public $fillable = [
        'name',
        'description',
        'default_set_id'
    ];
}
