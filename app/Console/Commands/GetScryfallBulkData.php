<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class GetScryfallBulkData extends Command
{
    protected $signature = 'scryfall:get-and-import-bulk-data {--force : Force re-importing even if filename matches}';
    protected $description = 'Download and import bulk pricing data from Scryfall using high-performance batch upserts';

    public function handle()
    {
        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $this->info('Checking Scryfall bulk data index...');
        $response = Http::withHeaders([
            'User-Agent' => 'DeckOWS/1.0 (deck optimizer tool)',
            'Accept' => 'application/json',
        ])->get('https://api.scryfall.com/bulk-data');

        if (!$response->successful()) {
            $this->error("Scryfall index request failed ({$response->status()}): " . $response->body());
            return Command::FAILURE;
        }

        $json = $response->json();
        if (!isset($json['data'])) {
            $this->error("Unexpected response shape: " . $response->body());
            return Command::FAILURE;
        }

        $defaultDump = collect($json['data'])->firstWhere('type', 'default_cards');

        if (!$defaultDump || !isset($defaultDump['jsonl_download_uri'])) {
            $this->error('Could not find default_cards entry in Scryfall bulk data.');
            return Command::FAILURE;
        }

        $downloadUri   = $defaultDump['jsonl_download_uri'];
        $gzFilename    = basename($downloadUri);
        $jsonlFilename = str_replace('.gz', '', $gzFilename);

        $lastImported = DB::table('scryfall_imports')
            ->orderByDesc('imported_at')
            ->value('filename');

        $storageDir = storage_path('app/scryfall');
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $gzPath    = "{$storageDir}/{$gzFilename}";
        $localPath = "{$storageDir}/{$jsonlFilename}";

        // Skip if already imported and --force is not passed
        if ($lastImported === $jsonlFilename && !$this->option('force')) {
            $this->info("File {$jsonlFilename} was already imported. Use --force to re-process.");
            return Command::SUCCESS;
        }

        // Download if file isn't present
        if (!file_exists($gzPath) && !file_exists($localPath)) {
            $this->info("Downloading new bulk file {$gzFilename}...");
            $client = new Client();
            $client->request('GET', $downloadUri, [
                'sink'    => $gzPath,
                'timeout' => 300,
                'headers' => [
                    'User-Agent' => 'DeckOWS/1.0 (deck optimizer tool)',
                    'Accept'     => 'application/json',
                ],
            ]);
        }

        // Decompress
        if (!file_exists($localPath)) {
            $this->info("Decompressing {$gzFilename}...");
            $ret = 0;
            passthru('gunzip -f ' . escapeshellarg($gzPath), $ret);
            if ($ret !== 0) {
                $this->error('❌ gunzip failed. Is gzip installed in the container?');
                return Command::FAILURE;
            }
        }

        if (!file_exists($localPath)) {
            $this->error('❌ File not found after decompression.');
            return Command::FAILURE;
        }

        // Cleanup old dumps
        foreach (glob("{$storageDir}/default-cards-*.jsonl") as $file) {
            if ($file !== $localPath) {
                @unlink($file);
            }
        }

        $this->info("Parsing and importing cards via bulk upsert...");
        $fh = fopen($localPath, 'r');
        
        $batch = [];
        $batchSize = 500;
        $totalCount = 0;
        $today = now()->toDateString();
        $nowTimestamp = now();

        while (($line = fgets($fh)) !== false) {
            $card = json_decode(trim($line), true);
            unset($line);

            if (!$card || !isset($card['oracle_id'])) {
                unset($card);
                continue;
            }

            // DFC Image Extraction Fallback
            $imageUrl = $card['image_uris']['normal'] 
                ?? $card['card_faces'][0]['image_uris']['normal'] 
                ?? null;

            $batch[] = [
                'scryfall_id'      => $card['id'],
                'oracle_id'        => $card['oracle_id'],
                'name'             => $card['name'],
                'set_name'         => $card['set'],
                'collector_number' => $card['collector_number'],
                'usd'              => $card['prices']['usd'] ?? null,
                'usd_foil'         => $card['prices']['usd_foil'] ?? null,
                'rarity'           => $card['rarity'],
                'released_at'      => $card['released_at'] ?? null,
                'image_uri'        => $imageUrl,
                'price_date'       => $today,
                'created_at'       => $nowTimestamp,
                'updated_at'       => $nowTimestamp,
            ];

            unset($card);

            if (count($batch) >= $batchSize) {
                DB::table('mtg_bulk_prices')->upsert(
                    $batch,
                    ['scryfall_id'],
                    ['oracle_id', 'name', 'set_name', 'collector_number', 'usd', 'usd_foil', 'rarity', 'released_at', 'image_uri', 'price_date', 'updated_at']
                );
                
                $totalCount += count($batch);
                $this->info("Upserted {$totalCount} cards...");
                
                $batch = [];
                gc_collect_cycles();
            }
        }
        fclose($fh);

        // Process final remaining batch
        if (!empty($batch)) {
            DB::table('mtg_bulk_prices')->upsert(
                $batch,
                ['scryfall_id'],
                ['oracle_id', 'name', 'set_name', 'collector_number', 'usd', 'usd_foil', 'rarity', 'released_at', 'image_uri', 'price_date', 'updated_at']
            );
            $totalCount += count($batch);
        }

        DB::table('scryfall_imports')->insert([
            'filename'    => $jsonlFilename,
            'imported_at' => now(),
        ]);

        $this->info("Done! Successfully bulk upserted {$totalCount} cards.");
        return Command::SUCCESS;
    }
}