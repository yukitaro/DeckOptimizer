<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use GuzzleHttp\Client;
use App\Models\MtgBulkPrices;
use JsonMachine\Items;

class GetScryfallBulkData extends Command
{
    protected $signature = 'scryfall:get-and-import-bulk-data';
    protected $description = 'Download and import bulk pricing data from Scryfall';

    public function handle()
    {
        $this->info('Checking Scryfall bulk data index...');
        $bulkList = Http::get('https://api.scryfall.com/bulk-data')->json()['data'];
        $defaultDump = collect($bulkList)->firstWhere('type', 'default_cards');
        $downloadUri = $defaultDump['download_uri'];
        $expectedFilename = basename($downloadUri);

        $alreadyImported = DB::table('scryfall_imports')
            ->where('filename', $expectedFilename)
            ->exists();

        if ($alreadyImported) {
            $this->info("Already imported {$expectedFilename}. Skipping.");
            return;
        }

        $localPath = storage_path("app/scryfall/{$expectedFilename}");
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        if (!file_exists($localPath)) {
            $client = new Client();
            $client->request('GET', $downloadUri, [
                'sink' => $localPath,
                'timeout' => 120,
            ]);
        }

        if (!file_exists($localPath)) {
            $this->error("❌ File not found after download attempt.");
            return;
        }

        $existingFiles = glob(storage_path('app/scryfall/default-cards-*.json'));
        foreach ($existingFiles as $file) {
            if ($file !== $localPath) {
                unlink($file);
            }
        }

        $this->info("Parsing and importing prices...");
        $stream = Items::fromFile($localPath);
        $count = 0;

        foreach ($stream as $card) {
            $card = $this->toArrayRecursive($card);

            if (!isset($card['oracle_id'])) {
                continue;
            }

            MtgBulkPrices::updateOrCreate([
                'scryfall_id' => $card['id'],
            ], [
                'oracle_id' => $card['oracle_id'],
                'name' => $card['name'],
                'set_name' => $card['set_name'],
                'collector_number' => $card['collector_number'],
                'usd' => $card['prices']['usd'] ?? null,
                'usd_foil' => $card['prices']['usd_foil'] ?? null,
                'rarity' => $card['rarity'],
                'released_at' => $card['released_at'],
                'image_uri' => $card['image_uris']['normal'] ?? null,
                'price_date' => now()->toDateString(),
            ]);

            $count++;
            if ($count % 1000 === 0) {
                $this->info("Imported $count cards...");
            }
        }

        DB::table('scryfall_imports')->insert([
            'filename' => $expectedFilename,
            'imported_at' => now(),
        ]);

        $this->info("Done. Imported $count cards.");
    }

    private function toArrayRecursive($data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->toArrayRecursive($value);
            }
        }
        return $data;
    }
}
