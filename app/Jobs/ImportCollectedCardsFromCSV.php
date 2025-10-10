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

    protected $mode;
    protected $records;
    protected $recordsKey;
    protected $collectionId;

    public function __construct($mode, $recordsKey, $collectionId)
    {
        $this->mode = $mode;
        $this->recordsKey = $recordsKey;
        $this->collectionId = $collectionId;
    }

    public function handle()
    {
        $records = Cache::pull($this->recordsKey);

        if (!is_array($records)) {
            Log::error("Import job failed: records payload missing or invalid", [
                'collectionManagementId' => $this->collectionId,
                'recordsKey' => $this->recordsKey,
            ]);
            return;
        }

Log::info("Import job triggered", [
    'collectionManagementId' => $this->collectionId,
    'recordCount' => count($records), // ✅ use $records
    'mode' => $this->mode,
]);

        $collection = CollectionManagement::find($this->collectionId);

        if (!$collection) {
            Log::error("CollectionManagement not found", ['collectionManagementId' => $this->collectionId]);
            return;
        }

        $collection->import_status = 'processing';
        $collection->save();

Log::info("Import started", [
    'collectionManagementId' => $collection->id,
    'collectionName' => $collection->collection_name ?? '(unnamed)',
    'recordCount' => count($records), // ✅ use $records
    'mode' => $this->mode,
]);

        try {

Log::info("About to call importToCollection", [
    'mode' => $this->mode,
    'recordsType' => gettype($this->records),
    'collectionIdType' => gettype($this->collectionId),
    'collectionIdValue' => $this->collectionId,
]);            
            CollectedCardsImportService::importToCollection(
                $this->mode,
                $records,
                $this->collectionId
            );

            $collection->import_status = 'complete';
            Log::info("Import job kicked off", [
                'collectionManagementId' => $collection->id,
                'status' => 'complete',
            ]);
        } catch (\Exception $e) {
            $collection->import_status = 'failed';
            Log::error("Import failed", [
                'collectionManagementId' => $collection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $collection->save();
    }
}