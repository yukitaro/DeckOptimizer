<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CollectedCardsFromSets;
use App\Models\CollectionOwner;
use App\Models\SetsInCollection;


class CollectionManagement extends Model
{
    protected $table = 'collection_management';

    public function collectionOwner() : HasOne
    {
        return $this->hasOne(CollectionOwner::class, 'collection_id');
    }

    public function setsInCollection() : HasMany
    {
        return $this->hasMany(SetsInCollection::class, 'collection_id');
    }

    public function cardsInCollection() : HasManyThrough
    {
        return $this->hasManyThrough(
            CollectedCardsFromSets::class,     // Final model
            SetsInCollection::class,      // Intermediate model
            'collection_id',              // Foreign key on SetsInCollection
            'id',                         // Foreign key on CardsInCollection
            'id',                         // Local key on CollectionManagement
            'sets_in_collections_id'      // Local key on SetsInCollection
        );
    }

    protected $fillable = [
        'owner_id',
        'collection_name',
        'description',
        'sets_in_collection_id'
    ];
}
