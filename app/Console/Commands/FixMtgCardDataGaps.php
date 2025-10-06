<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardDataNormalized;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use App\Models\MagicSetData;

class FixMtgCardDataGaps extends Command
{
    protected $signature = 'fix:mtg-card-data-gaps {--dry-run} {--only= : source-printing|metadata|set-data|all}';
    protected $description = 'Automates backfilling of missing source_printing_id, card_metadata_id, and magic_set_data_id';

    public function handle()
    {
        $only = $this->option('only') ?? 'all';
        $dryRun = $this->option('dry-run');

        if ($only === 'source-printing' || $only === 'all') {
            $this->fixSourcePrinting($dryRun);
        }

        if ($only === 'metadata' || $only === 'all') {
            $this->fixCardMetadata($dryRun);
        }

        if ($only === 'set-data' || $only === 'all') {
            $this->fixMagicSetData($dryRun);
        }

        $this->info('✅ Backfill complete.');
    }

    protected function fixSourcePrinting($dryRun)
    {
        $this->info('🔧 Fixing source_printing_id in card_data_normalized...');
        $count = 0;

        CardDataNormalized::whereNull('source_printing_id')->chunkById(100, function ($cards) use (&$count, $dryRun) {
            foreach ($cards as $cdn) {
                $match = CardDataFromSetData::whereRaw('LOWER(name) = ?', [strtolower($cdn->name)])
                    ->where('set_name', $cdn->set_code)
                    ->first();

                if ($match) {
                    if (!$dryRun) {
                        $cdn->source_printing_id = $match->id;
                        $cdn->save();
                    }
                    $count++;
                } else {
                    \Log::warning("No match for normalized card {$cdn->id} ({$cdn->name}, {$cdn->set_code})");
                }
            }
        });

        $this->info("✅ Patched {$count} source_printing_id values.");
    }

    protected function fixCardMetadata($dryRun)
    {
        $this->info('🔧 Fixing card_metadata_id in card_data_from_set_data using reverse foreign key...');
        $count = 0;

        CardMetadata::whereNotNull('card_data_from_set_data_id')->chunkById(100, function ($metas) use (&$count, $dryRun) {
            foreach ($metas as $meta) {
                $cd = CardDataFromSetData::find($meta->card_data_from_set_data_id);

                if ($cd && $cd->card_metadata_id === null) {
                    if (!$dryRun) {
                        $cd->card_metadata_id = $meta->id;
                        $cd->save();
                    }
                    $count++;
                }
            }
        });

        $this->info("✅ Patched {$count} card_metadata_id values.");
    }
}

