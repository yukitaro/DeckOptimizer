<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Services\CollectedCardsImportService;
use App\Models\CollectionManagement;

class ImportCollectedCardsFromCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // 15 minutes
    public int $tries = 1;     // don't retry — a failed partial import would need manual cleanup

    protected $mode;
    protected $records;
    protected $recordsKey;
    protected $collectionId;
    protected $shouldDedupe;

    public function __construct($mode, $recordsKey, $collectionId, $shouldDedupe = true)
    {
        $this->mode = $mode;
        $this->recordsKey = $recordsKey;
        $this->collectionId = $collectionId;
        $this->shouldDedupe = $shouldDedupe;
    }

    public function handle()
    {
        $records = Cache::get($this->recordsKey);

        if (!is_array($records)) {
            Log::error("Import job failed: records payload missing or invalid", [
                'collectionManagementId' => $this->collectionId,
                'recordsKey' => $this->recordsKey,
                'cacheExists' => Cache::has($this->recordsKey),
                'recordsType' => gettype($records),
                'recordsValue' => $records,
            ]);
            return;
        }

        $collection = CollectionManagement::find($this->collectionId);

        if (!$collection) {
            Log::error("CollectionManagement not found", ['collectionManagementId' => $this->collectionId]);
            return;
        }

        $collection->import_status = 'processing';
        $collection->save();

        try {
            CollectedCardsImportService::importToCollection(
                $this->mode,
                $records,
                $this->collectionId,
                $this->shouldDedupe
            );

            $collection->import_status = 'complete';
        } catch (\Exception $e) {
            $collection->import_status = 'failed';
            Log::error("Import failed", [
                'collectionManagementId' => $collection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $collection->save();
        Cache::forget($this->recordsKey);
    }
}