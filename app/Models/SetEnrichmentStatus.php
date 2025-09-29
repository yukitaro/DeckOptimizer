<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetEnrichmentStatus extends Model
{
    protected $table = 'set_enrichment_status';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'set_code',
        'enrichment_status',
        'last_enriched_at'
    ];
}
