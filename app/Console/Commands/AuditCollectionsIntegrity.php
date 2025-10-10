<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\CollectedCardsFromSets;
use App\Models\CollectionManagement;
use App\Models\SetsInCollection;
use App\Models\SetData;

class AuditCollectionsIntegrity extends Command
{
    protected $signature = 'audit:collections-integrity';
    protected $description = 'Audit collection cascade integrity and orphaned records';

    public function handle()
    {
        $this->info('🔍 Auditing collection integrity…');

        $totalCollections = CollectionManagement::count();
        $totalSets = SetsInCollection::count();
        $totalCards = CollectedCardsFromSets::count();

        $orphanedSets = SetsInCollection::whereDoesntHave('collection')->count();
        $orphanedCards = CollectedCardsFromSets::whereDoesntHave('setInCollection')->count();

        $this->table(
            ['Entity', 'Total', 'Orphaned', 'Healthy'],
            [
                ['Collections', $totalCollections, '-', $totalCollections],
                ['Sets in CollectionManagement', $totalSets, $orphanedSets, $totalSets - $orphanedSets],
                ['Collected Cards', $totalCards, $orphanedCards, $totalCards - $orphanedCards],
            ]
        );

        if ($orphanedSets > 0 || $orphanedCards > 0) {
            $this->warn('⚠️ Orphaned records detected. Cascade delete may not be working correctly.');
        } else {
            $this->info('✅ All relationships appear healthy.');
        }

        $this->line('');
        $this->info('📊 Hydration Coverage by CollectionManagement:');

        $rows = CollectionManagement::withCount([
            'setsInCollection as total_sets',
            'setsInCollection as hydrated_sets' => fn($q) => $q->whereHas('collectedCards'),
        ])->get()->map(function ($c) {
            $coverage = $c->total_sets > 0
                ? round(($c->hydrated_sets / $c->total_sets) * 100, 2)
                : 0;

            return [
                $c->name,
                $c->total_sets,
                $c->hydrated_sets,
                $c->total_sets - $c->hydrated_sets,
                "{$coverage}%",
            ];
        });

        $this->table(['CollectionManagement', 'Total Sets', 'Hydrated', 'Missing', 'Coverage'], $rows->toArray());

        $this->line('');

        $this->info('🧪 Verifying cascade delete behavior…');

        // Step 1: Create dummy SetData
        $dummySet = SetData::create([
            'official_set_code' => 'TESTSET-' . Str::uuid(),
            'set_name' => 'Cascade Audit Set',
            'release_date' => '2099-01-01', // arbitrary future date
            'total_cards' => '1',
            'cards_populated' => false,
        ]);

        // Step 2: Create dummy collection
        $testCollection = CollectionManagement::create([
            'owner_id' => 999999,
            'collection_name' => 'Cascade Test ' . Str::uuid(),
            'description' => 'Temporary collection for cascade audit'
        ]);

        // Step 3: Add dummy set to collection
        $testSet = SetsInCollection::create([
            'collection_id' => $testCollection->id,
            'set_id' => $dummySet->id,
            'set_name' => $dummySet->set_name,
        ]);

        // Step 4: Add dummy card to set
        $testCard = CollectedCardsFromSets::create([
            'set_in_collection_id' => $testSet->id,
            'card_data_id' => 1, // assuming ID 1 exists
            'card_count' => 1,
        ]);

        // Step 5: Delete the collection (should cascade)
        $testCollection->delete();

        // Step 6: Check if related records were deleted
        $setExists = SetsInCollection::where('id', $testSet->id)->exists();
        $cardExists = CollectedCardsFromSets::where('id', $testCard->id)->exists();

        if (!$setExists && !$cardExists) {
            $this->info('✅ Cascade delete verified: related sets and cards were removed.');
        } else {
            $this->warn('⚠️ Cascade delete failed: orphaned records remain.');
            $this->line("Set still exists: " . ($setExists ? 'Yes' : 'No'));
            $this->line("Card still exists: " . ($cardExists ? 'Yes' : 'No'));
        }

        // Step 7: Clean up dummy SetData (not part of cascade)
        $dummySet->delete();
    }
}