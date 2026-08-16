<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Seeder;

use Database\Seeders\SetDataSeeder;
use Database\Seeders\CardDataNormalizedSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\IssueTypesSeeder;
use Database\Seeders\SiteFeaturesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // php artisan migrate:fresh --seed for a new instance

        $this-call([
            SetDataSeeder::class,
            CardDataNormalizedSeeder::class,
            RolesAndPermissionsSeeder::class,
            IssueTypesSeeder::class,
            SiteFeaturesSeeder::class
        ]);
    }
}
