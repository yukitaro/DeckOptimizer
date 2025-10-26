<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SetDataSeeder;

class SeedSets extends Command
{
    protected $signature = 'seed:sets {--sets=} {--force} {--skip-scryfall-image-hydration} {--slug-only}';
    protected $description = 'Seed Magic set data with optional filtering by set code';

    public function handle()
    {
        $sets = $this->option('sets') ?? '';
        $force = $this->option('force') ? 'true' : 'false';
        $skipScryfallImageHydration = $this->option('skip-scryfall-image-hydration') ? 'true' : 'false';
        $slugOnly = $this->option('slug-only') ? 'true' : 'false';

        putenv("SEEDER_SETS={$sets}");
        putenv("SEEDER_FORCE={$force}");
        putenv("SEEDER_SKIP_SCRYFALL_IMAGE_HYDRATION={$skipScryfallImageHydration}");
        putenv("SEEDER_SLUG_ONLY={$slugOnly}");

        $this->call(\Database\Seeders\SetDataSeeder::class);
    }
}