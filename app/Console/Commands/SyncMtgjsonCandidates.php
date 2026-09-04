<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use JsonMachine\Items;

use App\Models\MtgJsonImportCandidate;
use App\Models\SetData;

class SyncMtgjsonCandidates extends Command
{
    protected $signature = 'sync:mtgjson-candidates {--force : Re-evaluate sets already marked as imported}';
    protected $description = 'Download and parse MTGJSON AllSetFiles to identify import-ready sets';

    public function handle()
    {
        $force = $this->option('force');
        $url = 'https://mtgjson.com/api/v5/AllSetFiles.tar.bz2';
        $localPath = storage_path('app/mtgjson/AllSetFiles.tar.bz2');
        $extractPath = storage_path('app/mtgjson/AllSetFiles');

        // Only download + extract if the archive or extracted dir doesn't exist yet
        if (!is_dir($extractPath) || count(glob("$extractPath/*.json")) === 0) {
            $this->info('🔄 Downloading AllSetFiles.tar.bz2...');

            if (!is_dir(storage_path('app/mtgjson'))) {
                mkdir(storage_path('app/mtgjson'), 0755, true);
            }

            $client = new \GuzzleHttp\Client();
            $client->get($url, [
                'sink' => $localPath,
                'timeout' => 120,
            ]);

            $this->info('📦 Extracting archive...');
            $ret = 0;
            passthru("tar -xjf " . escapeshellarg($localPath) . " -C " . escapeshellarg(storage_path('app/mtgjson')), $ret);
            if ($ret !== 0) {
                $this->error('❌ tar extraction failed (exit code ' . $ret . '). Is bzip2 installed?');
                return 1;
            }
        } else {
            $this->info('✅ Archive already extracted, skipping download.');
        }

        $files = collect(scandir($extractPath))
            ->filter(fn($f) => str_ends_with($f, '.json') && $f !== '.' && $f !== '..');

        foreach ($files as $filename) {
            $setCode = pathinfo($filename, PATHINFO_FILENAME);
            $jsonPath = "$extractPath/$filename";

            try {
                $setName = $this->readJsonScalar($jsonPath, '/data/name');
                $jsonReleaseDate = $this->readJsonScalar($jsonPath, '/data/releaseDate');
                $jsonFileDate = $this->readJsonScalar($jsonPath, '/meta/date');

                $total = 0;
                $meta = 0;
                $norm = 0;
                $img = 0;

                // Stream cards so very large sets (e.g. PLST/SLD) do not exhaust memory.
                foreach (Items::fromFile($jsonPath, ['pointer' => '/data/cards']) as $card) {
                    if (!is_array($card) && !is_object($card)) {
                        continue;
                    }

                    $total++;
                    if (!empty(data_get($card, 'identifiers.scryfallId'))) {
                        $meta++;
                    }
                    if (!empty(data_get($card, 'name')) || !empty(data_get($card, 'faceName'))) {
                        $norm++;
                    }
                    if (!empty(data_get($card, 'purchaseUrls')) || !empty(data_get($card, 'prices'))) {
                        $img++;
                    }
                }

                if ($total === 0) {
                    $this->warn("⚠️  Invalid JSON or empty cards for $setCode");
                    continue;
                }

                $metaPct = round($meta * 100 / $total, 2);
                $normPct = round($norm * 100 / $total, 2);
                $imgPct = round($img * 100 / $total, 2);

                // Try to get release date from DB or JSON.
                $set = SetData::where('set_name', $setCode)->first();
                $releaseDate = $set->release_date ?? $jsonReleaseDate;

                if (!$releaseDate) {
                    $this->warn("⚠️  No release date for $setCode");
                    continue;
                }

                $releaseDate = Carbon::parse($releaseDate);
                $ready = $releaseDate->gt(Carbon::parse('2025-09-06')) && $releaseDate->lte(Carbon::today());

                // Skip only if actually seeded in set_data (ground truth), unless --force.
                $actuallyImported = SetData::where('set_name', $setCode)
                    ->where('cards_populated', true)
                    ->exists();

                if ($actuallyImported && !$force) {
                    continue;
                }

                MtgJsonImportCandidate::updateOrCreate(
                    ['set_code' => $setCode],
                    [
                        'set_name' => is_string($setName) ? $setName : null,
                        'release_date' => $releaseDate,
                        'total_cards' => $total,
                        'metadata_count' => $meta,
                        'normalized_count' => $norm,
                        'image_count' => $img,
                        'metadata_pct' => $metaPct,
                        'normalization_pct' => $normPct,
                        'image_pct' => $imgPct,
                        'json_file_size_bytes' => filesize($jsonPath),
                        'json_file_date' => is_string($jsonFileDate) ? $jsonFileDate : null,
                        'ready_for_import' => $ready,
                        'imported_into_database' => false,
                    ]
                );

                // Copy to traceability path.
                $tracePath = base_path('data/AllSetFiles');
                if (!is_dir($tracePath)) {
                    mkdir($tracePath, 0755, true);
                }
                copy($jsonPath, "$tracePath/$filename");

                $this->line("✅ Processed $setCode — Ready: " . ($ready ? 'yes' : 'no'));
            } catch (\Throwable $e) {
                $this->warn("⚠️  Failed processing $setCode: {$e->getMessage()}");
                continue;
            }
        }
        $this->info('🎯 Sync complete.');
    }

    private function readJsonScalar(string $jsonPath, string $pointer)
    {
        foreach (Items::fromFile($jsonPath, ['pointer' => $pointer]) as $value) {
            return $value;
        }

        return null;
    }
}
