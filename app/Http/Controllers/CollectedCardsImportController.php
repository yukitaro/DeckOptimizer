<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use App\Jobs\ImportCollectedCardsFromCSV;
use App\Utilities\CsvImportUtility;

class CollectedCardsImportController extends Controller
{
    public function import(Request $request)
    {
        $file = $request->file('csv');
        $mode = $request->input('mode');
        $collectionId = $request->input('collection_id');

        $records = CsvImportUtility::parse($file);

        $cacheKey = 'import_records_' . Str::uuid();
        Cache::put($cacheKey, $records, now()->addMinutes(30));

Log::info("Dispatching import job", [
  'mode' => $mode,
  'recordsKey' => $cacheKey,
  'collectionId' => $collectionId,
]);

        ImportCollectedCardsFromCSV::dispatch($mode, $cacheKey, $collectionId);

        //ImportCollectedCardsFromCSV::dispatch($mode, $records, $collectionId)->delay(now());

        return response()->json(['status' => 'success']);
    }
}
