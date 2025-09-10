<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeckOwner extends Model
{
    protected $table = 'deck_owner';

    public function ownedDecks(): HasMany
    {
        // trying out the ability to define the foreign key name as a param
        return $this->hasMany(DeckManagement::class, 'deck_owner_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owern_login',
        'free_text',
        'deck_id',
    ];    
}
