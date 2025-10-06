<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardDataNormalized extends Model
{
    /** 
     * TODO: I could/should consider adding the hasMany relationship to the normalized card data
     * to allow easy reference back to see which decks a given card is in.
     * - migration to add a column
     * - hasMany relationship here
     * - consider having a card_versions table to associate data in this table back to the different version
     *   - can probably just execute a search for this though, don't necessarily need a table?
    */
    
    /**
     * The table associated with the model
     */
    protected $table = 'card_data_normalized';

/*     public function normalized(): HasOne
    {
        return $this->hasOne(CardDataNormalized::class, 'source_printing_id');
    } */

    public function sourceCard(): BelongsTo
    {
        return $this->belongsTo(CardDataFromSetData::class, 'source_printing_id');
    }

    public function metadata(): BelongsTo
    {
        return $this->belongsTo(CardMetadata::class, 'card_metadata_id');
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(SetData::class, 'magic_set_data_id');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'normalized_name',
        'set_code',
        'image_url_to_use',
        'printings',
        'source_printing_id'
    ];
}
