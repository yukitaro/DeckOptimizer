<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ItemList;
use App\Models\CardDataFromSetData;

class ListItems extends Model
{
    protected $table = 'list_items';

    protected $fillable = [
        'list_id',
        'item_type',
        'item_id',
        'quantity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function list()
    {
        return $this->belongsTo(ItemList::class, 'list_id');
    }

    public function card()
    {
        return $this->belongsTo(CardDataFromSetData::class, 'item_id');
    }


    public function item()
    {
        $map = [
            'card'           => \App\Models\CardDataFromSetData::class,
            'list'           => \App\Models\ItemList::class,
            'sealed_product' => \App\Models\SealedProduct::class,
        ];

        $model = $map[$this->item_type] ?? null;

        return $model ? $model::find($this->item_id) : null;
    }
}

