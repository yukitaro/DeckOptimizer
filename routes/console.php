<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\GetScryfallBulkData;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scryfall:get-and-import-bulk-data')
    ->dailyAt('03:00')
    ->sendOutputTo(storage_path('logs/scryfall_import.log'))
    ->runInBackground()
    ->withoutOverlapping()
    ->onSuccess(fn () => info('✅ Scryfall bulk price refresh completed.'))
    ->onFailure(fn () => info('❌ Scryfall bulk price refresh failed.'));