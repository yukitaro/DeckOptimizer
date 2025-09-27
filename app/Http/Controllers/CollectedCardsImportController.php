<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\CollectedCardsImportService;
use App\Utilities\CsvImportUtility;

class CollectedCardsImportController extends Controller
{
    public function import(Request $request)
    {
        $file = $request->file('csv');
        $mode = $request->input('mode');
        $collectionId = $request->input('collection_id');

        $records = CsvImportUtility::parse($file);

        CollectedCardsImportService::importToCollection($mode, $records, $collectionId);

        return response()->json(['status' => 'success']);
    }
}
