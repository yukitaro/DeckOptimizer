<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgArchetype extends Model
{
    protected $table = 'mtg_archetypes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'criteria',
        'format',
        'archetype_source_site',
        'archetype_source_site_id'
    ];
}
