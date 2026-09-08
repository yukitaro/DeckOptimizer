<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\CollectionManagement;
use App\Utilities\CollectionExportUtility;

class CollectionManagementController extends Controller
{
    public function destroy($id)
    {
        $collection = CollectionManagement::findOrFail($id);
        $collection->delete(); // assumes cascade is set up in DB
        return response()->json(['status' => 'deleted']);
    }

    public function export($id)
    {
        $collection = CollectionManagement::with('setsInCollection.collectedCards')->findOrFail($id);
        $csv = CollectionExportUtility::toCsv($collection); // or toJson()

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=collection_{$id}.csv");
    }

    public function update(UpdateCollectionRequest $request, CollectionManagement $collection)
    {
        // 1. $request->validated() contains ONLY keys sent in the payload
        // 2. Eloquent updates ONLY those specific columns in SQL
        $collection->update($request->validated());

        return response()->json([
            'message' => 'Collection updated successfully.',
            'data'    => $collection->fresh(),
        ]);
    }
}