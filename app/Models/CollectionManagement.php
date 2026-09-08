<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use App\Enums\Visibility;
use App\Models\CollectedCardsFromSets;
use App\Models\CollectionOwner;
use App\Models\SetsInCollection;
use App\Models\User;

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
            ->withPivot(['can_view', 'can_edit', 'can_share', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    public function isEditableBy(User $user): bool
    {
        return $this->owner_id === $user->id ||
            $this->delegates()
                ->where('user_id', $user->id)
                ->wherePivot('can_edit', true)
                ->exists();
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
        'collection_name',
        'description',
        'owner_id',
        'visibility',
        'import_status',
        'is_favorite',
        'include_in_inventory',
        'type',
        'game_type',
    ];

    protected $casts = [
        'is_favorite'          => 'boolean',
        'include_in_inventory' => 'boolean',
        'visibility' => Visibility::class,
    ];
}