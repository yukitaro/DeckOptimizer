<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GathererPrintsScraper;
use App\Models\CardDataFromSetData;

class TestGathererScraper extends Command
{
    protected $signature = 'test:gatherer-scraper {cardName}';
    protected $description = 'Test the Gatherer prints scraper';

    public function handle()
    {
        $cardName = $this->argument('cardName');
        $this->info("Testing scraper for: $cardName");

        $scraper = new GathererPrintsScraper();
        $prints = $scraper->scrapePrints($cardName);

        $this->info("Results:");
        dump($prints);

        // Test database lookup manually
        $this->info("--- Manual database test ---");
        $testCard = CardDataFromSetData::where('name', $cardName)
            ->where('set_name', 'M11')
            ->first();
            
        if ($testCard) {
            $this->info("Found $cardName in M11: " . $testCard->card_uuid);
        } else {
            $this->warn("$cardName in M11 not found");
        }

        // Show all entries for this card
        $this->info("--- All $cardName entries ---");
        $allCards = CardDataFromSetData::where('name', $cardName)->get(['set_name', 'card_uuid']);
        foreach ($allCards as $card) {
            $this->line("Set: {$card->set_name}, UUID: {$card->card_uuid}");
        }
    }
}