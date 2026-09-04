<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\GathererPrintsScraper;

use App\Models\CollectedCardsFromSets;
use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\MtgImageLookup;

class NormalizeImageUrlsForCollection extends Command
{
    protected $signature = 'app:normalize-image-urls-for-collection';
    protected $description = 'Scrape and normalize image URLs for all cards in the collection';

    public function handle()
    {
        $scraper = new GathererPrintsScraper();
        $skipped = 0;
        $updated = 0;

        CollectedCardsFromSets::with('cardFromSet')->chunk(100, function ($cards) use (&$scraper, &$skipped, &$updated) {
            foreach ($cards as $card) {
                $cardData = $card->cardFromSet;
                if (!$cardData) {
                    $skipped++;
                    continue;
                }

                $uuid = $cardData->card_uuid;
                $name = $cardData->name;
                $originalUrl = $cardData->image_url;
                $setCode = $cardData->set_code ?? $cardData->magic_set_data_id;

                $alreadyNormalized = CardDataFromSetData::where('card_uuid', $uuid)
                    ->whereNotNull('image_normalized_at')
                    ->exists();                

/*                 $alreadyExists = MtgImageLookup::where('card_uuid', $uuid)->exists();
                if ($alreadyExists) {
                    //Log::debug("Skipping {$name} — already normalized", ['uuid' => $uuid]);
                    $skipped++;
                    continue;
                } */
                if ($alreadyNormalized) {
                    $skipped++;
                    continue;
                }
                    
                try {
                    $prints = $scraper->scrapePrints($name);
                } catch (\Exception $e) {
                    $skipped++;
                    continue;
                }

                if (empty($prints)) {
                    $skipped++;
                    continue;
                }

                foreach ($prints as $index => $print) {
                    $canonicalUrl = $print['image_url'];
                    $printSetCode = $print['set_code'];
                    $printName = $print['card_name'];

                    $printUuid = CardDataFromSetData::where('name', $printName)
                        ->where('set_name', $printSetCode)
                        ->value('card_uuid');

                    if ($printUuid) {
                        $updateResult = CardDataFromSetData::where('card_uuid', $printUuid)
                            ->update(['image_url' => $canonicalUrl]);
                    }
                }   
            }
        });

        $this->warn("Completed! Updated {$updated} records, skipped {$skipped} records.");
    }
}
