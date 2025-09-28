<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\GathererPrintsScraper;

use App\Models\CollectedCardsFromSets;
use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\MtgImageLookup;

class NormalizeImageUrlsForCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-image-urls-for-collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scraper = new GathererPrintsScraper();
        $skipped = 0;
        $updated = 0;

        CollectedCardsFromSets::with('cardFromSet')->chunk(100, function ($cards) use (&$scraper, &$skipped,&$updated) {
            foreach ($cards as $card) {
                $cardData = $card->cardFromSet;
                $uuid = $cardData->card_uuid;
                $name = $cardData->name;
                $originalUrl = $cardData->image_url;
                $setCode = $cardData->set_code ?? $cardData->magic_set_data_id;

                $alreadyExists = MtgImageLookup::where('card_uuid', $cardData->card_uuid)->exists();

                if ($alreadyExists) {
                    $this->info("Skipping {$cardData->name} (UUID: {$cardData->card_uuid}) — already normalized.");
                    $skipped++;
                    continue;
                }                

                try {
                    $prints = $scraper->scrapePrints($cardData->name);
                } catch (\Exception $e) {
                    $this->warn("Failed to scrape prints for {$cardData->name}: {$e->getMessage()}");
                    $skipped++;
                    continue;
                }
                
                if (empty($prints)) {
                    $this->warn("No prints found for {$cardData->name}");
                    $skipped++;
                    continue;
                }

                foreach ($prints as $print) {
                    $canonicalUrl = $print['image_url'];
                    $setCode = $print['set_code'];

                    // Update lookup table
                    MtgImageLookup::updateOrCreate(
                        ['card_uuid' => $cardData->card_uuid],
                        [
                            'original_image_url' => $originalUrl,
                            'canonical_image_url' => $canonicalUrl,
                        ]
                    );

                    // Update card_data_from_set_data if we have a matching set
                    CardDataFromSetData::where('card_uuid', $cardData->card_uuid)
                        ->update(['image_url' => $canonicalUrl]);                    
                }

                // Pick canonical image for normalized view (match by set or fallback)
                $match = collect($prints)->firstWhere('set_code', $cardData->set_code) ?? $prints[0] ?? null;

                // Find the source printing ID
                $sourcePrinting = CardDataFromSetData::where('name', $name)
                    ->where('set_code', $match['set_code'])
                    ->first();                

                if ($match && !empty($match['image_url'])) {
                    CardDataNormalized::where('name', $cardData->name)->update([
                        'image_url_to_use' => $match['image_url'],
                        'set_code' => $match['set_code'],
                        'source_printing_id' => $sourcePrinting ? $sourcePrinting->id : null,
                    ]);
                    $updated++;
                } else {
                    $this->warn("No valid image found for {$cardData->name}");
                    $skipped++;
                }
            }
        });
        $this->warn("Completed! Updated {$updated} records, skipped {$skipped} records.");
    }
}
