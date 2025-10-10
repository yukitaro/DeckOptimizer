<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function imageCoverage()
    {
        $results = DB::table('card_data_from_set_data')
            ->select(
                'set_name',
                DB::raw('COUNT(*) AS total'),
                DB::raw('SUM(image_url IS NOT NULL) AS with_image'),
                DB::raw('SUM(image_url IS NULL) AS missing')
            )
            ->groupBy('set_name')
            ->orderByDesc(DB::raw('SUM(image_url IS NULL)'))
            ->get();

        // Add coverage percentage
        $data = $results->map(function ($row) {
            $coverage = $row->total > 0 ? round(($row->with_image / $row->total) * 100, 2) : 0;
            return [
                'set_name' => $row->set_name,
                'total' => $row->total,
                'with_image' => $row->with_image,
                'missing' => $row->missing,
                'coverage_percent' => $coverage
            ];
        });

        return response()->json($data);
    }
}
