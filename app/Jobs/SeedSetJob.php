<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use App\Models\MtgJsonImportCandidate;

class SeedSetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 600;  // 10 min max

    public function __construct(public string $setCode) {}

    public function handle(): void
    {
        Artisan::call('seed:sets', ['--sets' => $this->setCode]);
        MtgJsonImportCandidate::where('set_code', $this->setCode)
            ->update(['imported_into_database' => true]);
    }
}