<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SetDataSeeder;

class SeedSets extends Command
{
    protected $signature = 'seed:sets {--sets=}';
    protected $description = 'Seed Magic set data with optional filtering by set code';

    public function handle()
    {
        $sets = $this->option('sets') ?? '';

        // Pass the sets option as an environment variable
        putenv("SEEDER_SETS={$sets}");

        // Call the seeder
        $this->call(\Database\Seeders\SetDataSeeder::class);
    }
}