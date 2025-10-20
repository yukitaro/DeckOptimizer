<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\CardMetadata;
use App\Models\MtgImageLookup;

class PopulateImageLookupsFromScryfallData extends Command
{
    protected $signature = 'app:seed-image-lookups-from-scryfall 
                            {--normalized-only : Only update card_data_normalized table}
                            {--skip-insert : Skip the insert phase}
                            {--force : Force update all records even if they have URLs}';

    protected $description = 'Seed mtg_image_lookups for cards missing image_url using Scryfall bulk data';

    public function handle()
    {
        // Disable query log to save memory
        DB::connection()->disableQueryLog();
        
        $normalizedOnly = $this->option('normalized-only');
        $skipInsert = $this->option('skip-insert');
        $force = $this->option('force');
        
        if (!$skipInsert && !$normalizedOnly) {
            $this->info('Seeding image lookups from Scryfall bulk data…');

            // Load bulk file
            $path = collect(File::files(storage_path('app/scryfall')))
                ->filter(fn($f) => str_contains($f->getFilename(), 'default_cards'))
                ->sortByDesc(fn($f) => $f->getCTime())
                ->first()?->getPathname();

            if (!$path || !file_exists($path)) {
                $this->error("No bulk data file found.");
                return 1;
            }

            $this->info("Loading bulk data from: {$path}");
            $bulkCards = json_decode(file_get_contents($path), true);
            if (!is_array($bulkCards)) {
                $this->error("Failed to parse bulk data JSON.");
                return 1;
            }
            $this->info('Loaded ' . count($bulkCards) . ' cards from bulk data.');

            // Extract all scryfallIds from metadata
            $this->info('Extracting scryfallIds from card_metadata...');
            $scryfallIds = CardMetadata::query()
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(identifiers, '$.scryfallId')) IS NOT NULL")
                ->pluck("identifiers")
                ->map(fn($json) => json_decode($json, true)['scryfallId'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $this->info('Found ' . count($scryfallIds) . ' scryfallIds to hydrate.');

            // OPTIMIZATION: Convert to hash set for O(1) lookups instead of O(n) in_array()
            $scryfallIdsSet = array_flip($scryfallIds);

            // Build lookup map from bulk data - USE HASH SET LOOKUP
            $this->info('Building bulk data lookup map...');
            $bulkMap = collect($bulkCards)
                ->filter(fn($card) => isset($card['id']) && isset($scryfallIdsSet[$card['id']]))
                ->keyBy('id');

            $this->info('Found ' . $bulkMap->count() . ' matching cards in bulk data.');

            if ($bulkMap->isEmpty()) {
                $this->warn('No matching cards found. Nothing to seed.');
                return 0;
            }

            // Free memory
            unset($bulkCards);
            unset($scryfallIds);
            unset($scryfallIdsSet);
            gc_collect_cycles();

            // OPTIMIZATION: Batch insert instead of individual updateOrCreate calls
            $this->info('Preparing batch insert for image lookups...');
            $insertData = [];
            foreach ($bulkMap as $scryfallId => $card) {
                $insertData[] = [
                    'card_uuid' => $scryfallId,
                    'original_image_url' => $card['image_uris']['normal'] ?? null,
                    'canonical_image_url' => $card['image_uris']['normal'] ?? null,
                    'canonical_image_url_back' => $card['card_faces'][1]['image_uris']['normal'] ?? null,
                    'scryfall_image_uris' => json_encode($card['image_uris'] ?? null),
                    'hydrated_via_command' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert in chunks to avoid max packet size
            $this->info('Inserting ' . count($insertData) . ' records in batches of 1000...');
            $inserted = 0;
            foreach (array_chunk($insertData, 1000) as $index => $chunk) {
                DB::table('mtg_image_lookups')->upsert(
                    $chunk,
                    ['card_uuid'], // unique key
                    ['original_image_url', 'canonical_image_url', 'canonical_image_url_back', 'scryfall_image_uris', 'hydrated_via_command', 'updated_at']
                );
                $inserted += count($chunk);
                if (($index + 1) % 10 === 0) {
                    $this->info("  Processed {$inserted} records...");
                }
            }

            $this->info("✅ Seeded {$inserted} image lookups.");

            // Free memory
            unset($insertData);
            gc_collect_cycles();
        }

        if (!$normalizedOnly) {
            // Hydrate card_data_from_set_data.image_url using JOIN instead of loading all into memory
            $this->info("Hydrating image_url in card_data_from_set_data…");

            // Check how many would be updated
            $countCheck = DB::select("
                SELECT COUNT(*) as cnt
                FROM card_data_from_set_data cds
                INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
                INNER JOIN mtg_image_lookups mil ON mil.card_uuid = JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId'))
                WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL
            ");
            
            $this->info("Found {$countCheck[0]->cnt} matching records in card_data_from_set_data");

            $whereClause = $force 
                ? "WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL"
                : "WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL
                   AND (cds.image_url IS NULL OR cds.image_url != mil.canonical_image_url)";

            $updated = DB::update("
                UPDATE card_data_from_set_data cds
                INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
                INNER JOIN mtg_image_lookups mil ON mil.card_uuid = JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId'))
                SET cds.image_url = mil.canonical_image_url
                {$whereClause}
            ");

            $this->info("✅ card_data_from_set_data hydration complete. Total updated: {$updated}");
        }

        // Hydrate card_data_normalized.image_url_to_use using JOIN
        $this->info("Hydrating image_url_to_use in card_data_normalized…");
        
        // Check how many would be updated
        $normalizedCountCheck = DB::select("
            SELECT COUNT(*) as cnt
            FROM card_data_normalized cdn
            INNER JOIN card_data_from_set_data cds ON cdn.source_printing_id = cds.id
            INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
            INNER JOIN mtg_image_lookups mil ON mil.card_uuid = JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId'))
            WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL
        ");
        
        $this->info("Found {$normalizedCountCheck[0]->cnt} matching records in card_data_normalized");

        $whereClause = $force 
            ? "WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL"
            : "WHERE JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId')) IS NOT NULL
               AND (cdn.image_url_to_use IS NULL OR cdn.image_url_to_use != mil.canonical_image_url)";

        try {
            $normalizedUpdated = DB::update("
                UPDATE card_data_normalized cdn
                INNER JOIN card_data_from_set_data cds ON cdn.source_printing_id = cds.id
                INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
                INNER JOIN mtg_image_lookups mil ON mil.card_uuid = JSON_UNQUOTE(JSON_EXTRACT(cm.identifiers, '$.scryfallId'))
                SET cdn.image_url_to_use = mil.canonical_image_url
                {$whereClause}
            ");

            $this->info("✅ card_data_normalized hydration complete. Total updated: {$normalizedUpdated}");
        } catch (\Exception $e) {
            $this->error("Failed to update card_data_normalized: " . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        $this->info("🎉 All done!");
        if (!$normalizedOnly && !$skipInsert) {
            $this->info("  - Image lookups seeded: {$inserted}");
        }
        if (!$normalizedOnly) {
            $this->info("  - card_data_from_set_data updated: {$updated}");
        }
        $this->info("  - card_data_normalized updated: {$normalizedUpdated}");

        return 0;
    }
}