<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetsInCollection extends Model
{
    protected $table = 'sets_in_collection';

    public function collection() : BelongsTo
    {
        return $this->belongsTo(Collections::class, 'sets_in_collection_id');
    }

    public function magicSet() : BelongsTo
    {
        return $this->belongsTo(SetData::class);
    }

    public function collectedCards() : HasMany
    {
        return $this->hasMany(CollectedCardsFromSet::class, 'collected_cards_from_sets_id');
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'collection_id',
        'set_id'
    ];
}
