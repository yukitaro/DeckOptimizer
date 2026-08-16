<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteFeatures extends Model
{
    protected $table = 'site_features';

    public function issues() {
        return $this->hasMany(Issue::class, 'site_feature_id');
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function editor() { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeEnabled($q) { return $q->where('is_enabled', true); }
    public function scopeForMode($q, $mode) { return $q->where('site_mode', $mode); }

    protected $fillable = [
        'site_mode',
        'feature_name',
        'slug',
        'description',
        'is_enabled',
        'is_global',
        'sort_order',
        'meta',
        'created_by',
        'updated_by',
        'deprecated_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_global' => 'boolean',
        'meta' => 'array',
        'deprecated_at' => 'datetime',
    ];    
}
