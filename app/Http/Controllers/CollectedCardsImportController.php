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
        $validated = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
            'mode' => ['required', 'in:merge,set,new'],
            'collection_id' => ['required', 'integer'],
        ]);

        $file = $request->file('csv');
        $mode = $validated['mode'];
        $collectionId = (int) $validated['collection_id'];

        $records = CsvImportUtility::parse($file);

        $cacheKey = 'import_records_' . Str::uuid();
        Cache::put($cacheKey, $records, now()->addMinutes(30));

Log::info('Dispatching import job', [
    'mode' => $mode,
    'collectionId' => $collectionId,
    'recordCount' => count($records),
    'cacheKey' => $cacheKey
]);        
        ImportCollectedCardsFromCSV::dispatch($mode, $cacheKey, $collectionId);

        //ImportCollectedCardsFromCSV::dispatch($mode, $records, $collectionId)->delay(now());

        return response()->json(['status' => 'success']);
    }
}
