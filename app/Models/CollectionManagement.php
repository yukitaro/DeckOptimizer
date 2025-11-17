<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\Visibility;
use App\Models\CollectedCardsFromSets;
use App\Models\CollectionOwner;
use App\Models\SetsInCollection;


class CollectionManagement extends Model
{
    protected $table = 'collection_management';

    public function collectionOwner() : HasOne
    {
        return $this->hasOne(CollectionOwner::class, 'collection_management_id');
    }

    public function setsInCollection() : HasMany
    {
        return $this->hasMany(SetsInCollection::class, 'collection_management_id');
    }

    public function delegates()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('can_edit')
            ->withTimestamps();
    }


    public function cardsInCollection(): HasManyThrough
    {
        return $this->hasManyThrough(
            CollectedCardsFromSets::class,     // Final model
            SetsInCollection::class,           // Intermediate model
            'collection_management_id',        // Foreign key on SetsInCollection
            'set_in_collection_id',            // Foreign key on CollectedCardsFromSets
            'id',                              // Local key on CollectionManagement
            'id'                               // Local key on SetsInCollection
        );
    }

    protected $fillable = [
        'owner_id',
        'collection_name',
        'description',
        'visibility',
    ];

    protected $casts = [
        'visibility' => Visibility::class,
    ];
}