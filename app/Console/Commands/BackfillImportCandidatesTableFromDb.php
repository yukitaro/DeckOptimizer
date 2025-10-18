<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\MtgJsonImportCandidate;
use App\Models\SetData;
use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;

class BackfillImportCandidatesTableFromDb extends Command
{
    protected $signature = 'backfill:import-candidates-from-db';
    protected $description = 'Populate mtg_json_import_candidates using actual imported card data for sets released on or before 2025-09-06';

    public function handle()
    {
        $cutoff = Carbon::parse('2025-09-06');

        $sets = SetData::get();

        if ($sets->isEmpty()) {
            $this->info('✅ No eligible sets to backfill.');
            return;
        }

        foreach ($sets as $set) {
            $setCode = $set->set_name;
            $releaseDate = $set->release_date;

            $cards = CardDataFromSetData::where('magic_set_data_id', $set->id)->get();
            $total = $cards->count();

            if ($total === 0) {
                $this->warn("⚠️  Skipping $setCode — no cards found");
                continue;
            }

            $meta = $cards->whereNotNull('card_metadata_id')->count();
            $norm = CardDataNormalized::where('set_code', $setCode)->count();
            $img = $cards->whereNotNull('image_url')->count();

            $metaPct = round($meta * 100 / $total, 2);
            $normPct = round($norm * 100 / $total, 2);
            $imgPct = round($img * 100 / $total, 2);

            $ready = Carbon::parse($releaseDate)->gt($cutoff);

            MtgJsonImportCandidate::updateOrCreate(
                ['set_code' => $setCode],
                [
                    'set_name' => $set->official_set_code,
                    'release_date' => $releaseDate,
                    'total_cards' => $total,
                    'metadata_count' => $meta,
                    'normalized_count' => $norm,
                    'image_count' => $img,
                    'metadata_pct' => $metaPct,
                    'normalization_pct' => $normPct,
                    'image_pct' => $imgPct,
                    'json_file_size_bytes' => null,
                    'json_file_date' => null,
                    'ready_for_import' => $ready,
                    'imported_into_database' => $ready ? false : true,
                ]
            );

            $this->line("✅ Backfilled $setCode — Ready: " . ($ready ? 'yes' : 'no'));
        }

        $this->info('🎯 Backfill complete.');
    }
}