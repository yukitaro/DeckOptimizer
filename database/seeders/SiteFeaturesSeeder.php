<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\SiteFeatures; // adjust namespace if different
use Illuminate\Support\Facades\DB;

class SiteFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $siteFeaturesConfig = config('sitefeatures');

        if (!is_array($siteFeaturesConfig)) {
            $this->command->error('sitefeatures config not found or invalid.');
            return;
        }

        DB::transaction(function () use ($siteFeaturesConfig) {
            foreach ($siteFeaturesConfig as $siteMode => $features) {
                foreach ($features as $slug => $attrs) {
                    // normalize slug: prefer provided key, fallback to slugified feature_name
                    $slugNormalized = $slug ?: Str::slug($attrs['feature_name'] ?? 'feature');

                    $defaults = [
                        'site_mode'    => $siteMode,
                        'feature_name' => $attrs['feature_name'] ?? ucfirst(str_replace('-', ' ', $slugNormalized)),
                        'slug'         => $slugNormalized,
                        'description'  => $attrs['description'] ?? null,
                        'is_enabled'   => $attrs['is_enabled'] ?? true,
                        'is_global'    => $attrs['is_global'] ?? false,
                        'sort_order'   => $attrs['sort_order'] ?? 0,
                        'meta'         => $attrs['meta'] ?? null,
                    ];

                    // Use composite unique keys: site_mode + slug (or site_mode + feature_name)
                    SiteFeatures::updateOrCreate(
                        ['site_mode' => $siteMode, 'slug' => $slugNormalized],
                        $defaults
                    );
                }
            }
        });

        $this->command->info('Site features seeded/updated.');
    }
}