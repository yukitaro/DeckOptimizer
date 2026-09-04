<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ListItems;
    
class ItemList extends Model
{
    protected $table = 'lists';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'list_type',
        'is_public',
    ];

    public function items()
    {
        return $this->hasMany(ListItems::class, 'list_id');
    }
}