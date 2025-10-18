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

    public function dataCoverage()
    {
        $setCode = request()->route('set_name');

        $query = DB::table('card_data_from_set_data as raw')
            ->select([
                'raw.set_name',
                'msd.release_date',
                DB::raw('COUNT(raw.id) AS total_raw_cards'),
                DB::raw('COUNT(DISTINCT meta.id) AS metadata_linked'),
                DB::raw('COUNT(DISTINCT norm.id) AS normalized_cards'),
                DB::raw('COUNT(DISTINCT img.id) AS image_hydrated'),

                DB::raw('ROUND(COUNT(DISTINCT meta.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS metadata_pct'),
                DB::raw('ROUND(COUNT(DISTINCT norm.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS normalization_pct'),
                DB::raw('ROUND(COUNT(DISTINCT img.id) * 100.0 / NULLIF(COUNT(raw.id), 0), 2) AS image_pct'),

                DB::raw('SUM(CASE WHEN raw.set_name IS NULL THEN 1 ELSE 0 END) AS missing_set_name_count'),
                DB::raw('SUM(CASE WHEN meta.last_enriched_at IS NULL THEN 1 ELSE 0 END) AS missing_enrichment_timestamp'),
                DB::raw('MIN(meta.last_enriched_at) AS earliest_enrichment'),
                DB::raw('MAX(meta.last_enriched_at) AS latest_enrichment'),
                DB::raw('COUNT(DISTINCT meta.logic_version) AS logic_versions_used'),
                DB::raw('SUM(CASE WHEN img.hydrated_via_command = 1 THEN 1 ELSE 0 END) AS image_hydrated_via_command'),
            ])
            ->leftJoin('card_metadata as meta', 'meta.card_data_from_set_data_id', '=', 'raw.id')
            ->leftJoin('card_data_normalized as norm', function ($join) {
                $join->on('norm.normalized_name', '=', 'meta.normalized_name')
                    ->on('norm.set_code', '=', 'raw.set_name');
            })
            ->leftJoin('mtg_image_lookups as img', 'img.card_uuid', '=', 'raw.card_uuid')
            ->leftJoin('magic_set_data as msd', 'msd.set_name', '=', 'raw.set_name')
            ->whereNotNull('raw.set_name');

        if ($setCode) {
            $query->where('raw.set_name', $setCode);
        }

        $coverageStats = $query
            ->groupBy('raw.set_name', 'msd.release_date')
            ->havingRaw('COUNT(raw.id) >= 78')
            ->orderBy('msd.release_date', 'asc')
            ->get();

        return response()->json($coverageStats);
    }
}
