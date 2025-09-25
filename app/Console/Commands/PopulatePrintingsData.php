<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\CardDataNormalized;
use App\Models\CardDataFromSetData;

class PopulatePrintingsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-image-url-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute to populate the image_url data for Cards where it doesn\'t exist yet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate missing image url and set_code data...');
        
        $totalToProcess = CardDataNormalized::whereNull('image_url_to_use')
            ->orWhereNull('set_code')
            ->count();
        
        $this->info("Found {$totalToProcess} records to process");
        
        $processed = 0;
        $updated = 0;
        
        CardDataNormalized::whereNull('image_url_to_use')
            ->orWhereNull('set_code')
            ->with('sourcePrinting')
            ->chunk(100, function($cards) use (&$processed, &$updated) {
                foreach ($cards as $card) {
                    $processed++;
                    
                    if ($card->sourcePrinting) {
                        $updates = [];
                        
                        // First try to get data from source printing
                        if (!$card->image_url_to_use && $card->sourcePrinting->image_url) {
                            $updates['image_url_to_use'] = $card->sourcePrinting->image_url;
                        }
                        
                        if (!$card->set_code && $card->sourcePrinting->set_name) {
                            $updates['set_code'] = $card->sourcePrinting->set_name;
                        }

                        // Then try fallback for missing image_url
                        if (!$card->image_url_to_use && !isset($updates['image_url_to_use'])) {
                            $alternativePrinting = CardDataFromSetData::where('name', $card->name)
                                ->whereNotNull('image_url')
                                ->first();
                            if ($alternativePrinting) {
                                $updates['image_url_to_use'] = $alternativePrinting->image_url;
                            }
                        }                        
                        
                        if (!empty($updates)) {
                            $card->update($updates);
                            $updated++;
                            
                            if ($updated % 100 == 0) {
                                $this->info("Updated {$updated} records so far...");
                            }
                        }
                    } else {
                        $this->warn("No source printing found for card: {$card->name} (ID: {$card->id})");
                    }
                }
                
                $this->info("Processed {$processed} records, updated {$updated}");
            });
        
        $this->info("Completed! Total updated: {$updated} out of {$processed} processed");
    }
}
