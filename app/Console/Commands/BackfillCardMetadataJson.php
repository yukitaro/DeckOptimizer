<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use Cerbero\JsonParser\JsonParser;

class BackfillCardMetadataJson extends Command
{
    protected $signature = 'cards:backfill-json {--limit=0 : Limit records to process} {--dry-run : Show what would be updated} {--sample=10 : Show detailed output for N random records}';
    protected $description = 'Backfill missing identifiers and purchaseUrls JSON data in card_metadata';

public function handle()
    {
        $limit = (int)$this->option('limit');
        $dry = $this->option('dry-run');
        $sampleSize = (int)$this->option('sample');
        
        // Find metadata records missing identifiers or purchaseUrls
        $query = CardMetadata::whereNull('identifiers')
            ->orWhereNull('purchaseUrls')
            ->whereNotNull('card_data_from_set_data_id');
            
        $totalCount = $query->count();
        $this->info("Found {$totalCount} records that need processing");
        
        if ($dry && $sampleSize > 0) {
            // For dry run, just process a sample
            $this->info("DRY RUN: Processing random sample of {$sampleSize} records");
            $metadataRecords = $query->inRandomOrder()->limit($sampleSize)->get();
        } else {
            if ($limit > 0) {
                $query->limit($limit);
                $this->info("Limited to {$limit} records");
            }
            $metadataRecords = $query->get();
        }
        
        if ($dry) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }
        
        // Get the cards and their set names (which are actually set codes)
        $cardsBySet = [];
        foreach ($metadataRecords as $metadata) {
            $cardRow = CardDataFromSetData::find($metadata->card_data_from_set_data_id);
            if ($cardRow && $cardRow->card_uuid && $cardRow->set_name) {
                $cardsBySet[$cardRow->set_name][] = [
                    'metadata' => $metadata,
                    'card' => $cardRow
                ];
            }
        }
        
        $uniqueSets = array_keys($cardsBySet);
        $this->info("Need to load " . count($uniqueSets) . " JSON files for sets: " . implode(', ', $uniqueSets));
        
        $updated = 0;
        $processed = 0;
        $cardLookup = [];
        
        // Load only the specific JSON files we need
        $bar = $this->output->createProgressBar(count($uniqueSets));
        $bar->setFormat('Loading sets: %current%/%max% [%bar%] %percent:3s%%');
        
        foreach ($uniqueSets as $setName) {
            $jsonFile = base_path("data/AllSetFiles/{$setName}.json");
            
            if (!file_exists($jsonFile)) {
                $this->warn("⚠️  JSON file not found for set: {$setName}");
                $bar->advance();
                continue;
            }
            
            // Use the same pattern as SetDataSeeder
            $jsonCardData = JsonParser::parse($jsonFile)->pointer('/data/cards');
            foreach ($jsonCardData as $key => $aCardData) {
                if (is_array($aCardData)) {
                    foreach ($aCardData as $aCardFromSet) {
                        if (isset($aCardFromSet['uuid'])) {
                            $cardLookup[$aCardFromSet['uuid']] = $aCardFromSet;
                        }
                    }
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Processing {$metadataRecords->count()} metadata records...");
        
        foreach ($metadataRecords as $metadata) {
            $processed++;
            $cardRow = CardDataFromSetData::find($metadata->card_data_from_set_data_id);
            if (!$cardRow || !$cardRow->card_uuid) {
                continue;
            }
            
            $cardJson = $cardLookup[$cardRow->card_uuid] ?? null;
            if (!$cardJson) {
                $this->warn("Card UUID {$cardRow->card_uuid} not found in JSON for {$cardRow->name} from set {$cardRow->set_name}");
                continue;
            }
            
            $changes = [];
            $willUpdate = false;
            
            if (!$metadata->identifiers && !empty($cardJson['identifiers'])) {
                $changes[] = 'identifiers';
                $willUpdate = true;
                if (!$dry) $metadata->identifiers = $cardJson['identifiers'];
            }
            
            if (!$metadata->purchaseUrls && !empty($cardJson['purchaseUrls'])) {
                $changes[] = 'purchaseUrls';
                $willUpdate = true;
                if (!$dry) $metadata->purchaseUrls = $cardJson['purchaseUrls'];
            }
            
            if ($willUpdate) {
                if (!$dry) $metadata->save();
                $updated++;
                
                // Show detailed output for all records in dry run mode or sample
                if ($dry || $metadataRecords->count() <= 50) {
                    $this->line("📝 <info>Update #{$updated}</info> - Metadata ID: <comment>{$metadata->id}</comment>");
                    $this->line("   Card: <comment>{$cardRow->name}</comment> from set <comment>{$cardRow->set_name}</comment>");
                    $this->line("   Fields updated: <comment>" . implode(', ', $changes) . "</comment>");
                    
                    if (in_array('identifiers', $changes)) {
                        $ids = $cardJson['identifiers'];
                        $this->line("   Identifiers: scryfallId=" . ($ids['scryfallId'] ?? 'none') . 
                                  ", multiverseId=" . ($ids['multiverseId'] ?? 'none'));
                    }
                    
                    if (in_array('purchaseUrls', $changes)) {
                        $urls = $cardJson['purchaseUrls'];
                        $this->line("   Purchase URLs: " . implode(', ', array_keys($urls)));
                    }
                    $this->newLine();
                }
            }
        }
        
        $this->info("✅ <info>Processing Complete!</info>");
        $this->table(['Metric', 'Count'], [
            ['Records processed', number_format($processed)],
            ['Records updated', number_format($updated)],
            ['JSON files loaded', number_format(count($uniqueSets))],
            ['Success rate', round(($updated / max($processed, 1)) * 100, 1) . '%']
        ]);
        
        if ($dry) {
            $this->warn("This was a DRY RUN. Run without --dry-run to actually save changes.");
            if ($totalCount > $sampleSize) {
                $this->info("Estimated total updates: ~" . number_format(($updated / $sampleSize) * $totalCount));
            }
        }
        
        return 0;
    }
}
