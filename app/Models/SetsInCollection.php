<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CollectionManagement;
use App\Models\CollectedCardsFromSets;
use App\Models\SetData;

class SetsInCollection extends Model
{
    protected $table = 'sets_in_collection';

    public function collection() : BelongsTo
    {
        return $this->belongsTo(CollectionManagement::class, 'collection_management_id');
    }

    public function collectionManagement()
    {
        return $this->belongsTo(CollectionManagement::class, 'collection_management_id');
    }    

    public function magicSet() : BelongsTo
    {
        return $this->belongsTo(SetData::class);
    }

    public function collectedCards() : HasMany
    {
        return $this->hasMany(CollectedCardsFromSets::class, 'set_in_collection_id');
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'collection_management_id',
        'set_id'
    ];
}
