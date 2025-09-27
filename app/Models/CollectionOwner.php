<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionOwner extends Model
{
    protected $table = 'collection_owner';

    public function ownedCollections(): HasMany
    {
        return $this->hasMany(CollectionManagement::class, 'owner_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_login',
        'free_text',
        'collection_id',
    ];
}
