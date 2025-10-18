<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtgJsonImportCandidate extends Model
{
    protected $table = 'mtg_json_import_candidates';

    public function scopeReady($query)
    {
        return $query->where('ready_for_import', true)->where('imported_into_database', false);
    }

    public function scopeUnimported($query)
    {
        return $query->where('imported_into_database', false);
    }

    protected $fillable = [
        'set_code',
        'set_name',
        'release_date',
        'total_cards',
        'metadata_count',
        'normalized_count',
        'image_count',
        'metadata_pct',
        'normalization_pct',
        'image_pct',
        'json_file_size_bytes',
        'json_file_date',
        'imported_into_database',
        'ready_for_import',
    ];
}
