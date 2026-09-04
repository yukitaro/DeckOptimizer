<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IssueType extends Model
{
    use HasFactory;

    /** Use guarded or fillable depending on your preference */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'version',
    ];

    /** Casts */
    protected $casts = [
        'version' => 'integer',
    ];

    /**
     * Ensure a slug exists when creating
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
            if (is_null($model->version)) {
                $model->version = 1;
            }
        });
    }

    /**
     * Optional: scope to find by slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}
