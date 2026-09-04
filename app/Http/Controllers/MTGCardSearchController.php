<?php

namespace App\Http\Controllers;

use App\Http\Resources\MTGCardResource;
use App\Models\CardDataFromSetData;
use Illuminate\Http\Request;

class MTGCardSearchController extends Controller
{
    public function search(Request $request)
    {        
        $limit = min(max((int) $request->input('limit', 50), 1), 500);

        $query = CardDataFromSetData::query()
            // Eager load metadata and its associated bulk price
            ->with(['cardMetadata', 'cardMetadata.prices', 'collectedCards']);

        // 1. Text Search (Name)
        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }

        // 2. Set Filtering (Exact match using whereIn)
        if ($request->filled('sets')) {
            $sets = $request->input('sets');
            $setList = is_array($sets) ? $sets : explode(',', $sets);
            $cleanSets = array_values(array_filter(array_map('trim', $setList)));

            if (!empty($cleanSets)) {
                $query->whereIn('set_name', $cleanSets);
            }
        }

        // 3. Rarity Filtering
        if ($request->filled('rarities')) {
            $rarities = $request->input('rarities');
            $rarityList = is_array($rarities) ? $rarities : explode(',', $rarities);
            $cleanRarities = array_map('strtolower', array_filter($rarityList));

            $query->whereIn('rarity', $cleanRarities);
        }

        // 4. Color Filtering (Inclusive OR using FIND_IN_SET)
        if ($request->has('colors')) {
            $rawColors = $request->input('colors');

            // Handle both ?colors[]=B&colors[]=R AND ?colors=B,R
            if (is_string($rawColors)) {
                $colorList = explode(',', $rawColors);
            } elseif (is_array($rawColors)) {
                $colorList = $rawColors;
            } else {
                $colorList = [];
            }

            $cleanColors = array_values(array_filter(array_map(function ($c) {
                return strtoupper(trim($c));
            }, $colorList)));

            if (!empty($cleanColors)) {
                $query->where(function ($q) use ($cleanColors) {
                    foreach ($cleanColors as $color) {
                        // Choice A: Standard MySQL FIND_IN_SET for strict CSV strings like 'B,R' or 'R'
                        $q->orWhereRaw('FIND_IN_SET(?, colors) > 0', [$color]);

                        // Choice B: If colors column is stored as JSON like '["B","R"]'
                        // $q->orWhereJsonContains('colors', $color);

                        // Choice C: Safe fallback if spaces exist around commas like 'B, R'
                        // $q->orWhere('colors', 'LIKE', '%' . $color . '%');
                    }
                });
            }
        }

        // 5. Date Range Filtering
        if ($request->filled('date_start') || $request->filled('date_end')) {
            $start = $request->input('date_start');
            $end = $request->input('date_end');

            if ($start) {
                $query->whereRaw("STR_TO_DATE(release_date, '%Y-%m-%d') >= ?", [$start]);
            }

            if ($end) {
                $query->whereRaw("STR_TO_DATE(release_date, '%Y-%m-%d') <= ?", [$end]);
            }
        }
        //dd($query->toSql(), $query->getBindings());

        return MTGCardResource::collection($query->paginate($limit));
    }
}