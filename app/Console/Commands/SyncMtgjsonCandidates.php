<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\MtgJsonImportCandidate;
use App\Models\SetData;

use PharData;

class SyncMtgjsonCandidates extends Command
{
    protected $signature = 'sync:mtgjson-candidates';
    protected $description = 'Download and parse MTGJSON AllSetFiles to identify import-ready sets';

    public function handle()
    {
        $this->info('🔄 Downloading AllSetFiles.tar.bz2...');
        $url = 'https://mtgjson.com/api/v5/AllSetFiles.tar.bz2';
        $localPath = storage_path('app/mtgjson/AllSetFiles.tar.bz2');
        $extractPath = storage_path('app/mtgjson');

        // Ensure clean directory
        if (is_dir($extractPath)) {
            collect(scandir($extractPath))->each(function ($file) use ($extractPath) {
                if (!in_array($file, ['.', '..'])) {
                    unlink("$extractPath/$file");
                }
            });
        } else {
            mkdir($extractPath, 0755, true);
        }

        // Download archive
        $response = Http::timeout(60)->get($url);
        file_put_contents($localPath, $response->body());

        // Extract archive
        $this->info('📦 Extracting archive...');
        $phar = new PharData($localPath);
        $phar->decompress(); // creates .tar
        $tarPath = str_replace('.bz2', '', $localPath);
        $tar = new PharData($tarPath);
        $tar->extractTo($extractPath, null, true);

        // Get eligible sets
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(14);

        $files = collect(scandir($extractPath))
            ->filter(fn($f) => str_ends_with($f, '.json'));

        foreach ($files as $filename) {
            $setCode = pathinfo($filename, PATHINFO_FILENAME);
            $jsonPath = "$extractPath/$filename";
            $json = json_decode(file_get_contents($jsonPath), true);

            if (!$json || !isset($json['data']['cards'])) {
                $this->warn("⚠️  Invalid JSON for $setCode");
                continue;
            }

            $cards = $json['data']['cards'];
            $total = count($cards);
            $meta = collect($cards)->filter(fn($c) => isset($c['identifiers']['scryfallId']))->count();
            $norm = collect($cards)->filter(fn($c) => isset($c['name']) || isset($c['faceName']))->count();
            $img = collect($cards)->filter(fn($c) => !empty($c['purchaseUrls']) || !empty($c['prices']))->count();

            $metaPct = round($meta * 100 / max($total, 1), 2);
            $normPct = round($norm * 100 / max($total, 1), 2);
            $imgPct = round($img * 100 / max($total, 1), 2);

            // Try to get release date from DB or JSON
            $set = SetData::where('set_name', $setCode)->first();
            $releaseDate = $set->release_date ?? ($json['data']['releaseDate'] ?? null);

            if (!$releaseDate) {
                $this->warn("⚠️  No release date for $setCode");
                continue;
            }

            $releaseDate = Carbon::parse($releaseDate);
            $ready = $releaseDate->gt(Carbon::parse('2025-09-06'));

            // Skip if already imported
            $existing = MtgJsonImportCandidate::where('set_code', $setCode)
                ->where('imported_into_database', true)
                ->first();

            if ($existing) {
                $this->line("⏭️  Skipping $setCode (already imported)");
                continue;
            }

            MtgJsonImportCandidate::updateOrCreate(
                ['set_code' => $setCode],
                [
                    'set_name' => $json['data']['name'] ?? null,
                    'release_date' => $releaseDate,
                    'total_cards' => $total,
                    'metadata_count' => $meta,
                    'normalized_count' => $norm,
                    'image_count' => $img,
                    'metadata_pct' => $metaPct,
                    'normalization_pct' => $normPct,
                    'image_pct' => $imgPct,
                    'json_file_size_bytes' => filesize($jsonPath),
                    'json_file_date' => $json['meta']['date'] ?? null,
                    'ready_for_import' => $ready,
                    'imported_into_database' => false,
                ]
            );

            // ✅ Copy to traceability path
            $tracePath = base_path('data/AllSetFiles');
            if (!is_dir($tracePath)) {
                mkdir($tracePath, 0755, true);
            }
            copy($jsonPath, "$tracePath/$filename");

            $this->line("✅ Processed $setCode — Ready: " . ($ready ? 'yes' : 'no'));
        }
        $this->info('🎯 Sync complete.');
    }
}
