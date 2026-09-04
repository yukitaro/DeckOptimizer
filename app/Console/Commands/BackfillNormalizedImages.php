<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardDataNormalized;
use App\Models\CardDataFromSetData;
use App\Models\MtgImageLookup;
use Illuminate\Support\Facades\Log;

class BackfillNormalizedImages extends Command
{
    protected $signature = 'cards:backfill-images';
    protected $description = 'Backfill image_url_to_use in card_data_normalized using MtgImageLookup or Gatherer fallback';

    public function handle()
    {
        $cards = CardDataNormalized::with('sourceCard')->get();
        $this->output->progressStart($cards->count());

        foreach ($cards as $card) {
            $this->output->progressAdvance();

            $normalizedName = strtolower(trim($card->normalized_name));
            $imageUrl = null;

            // Get all printings with same normalized name
            $printings = CardDataFromSetData::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->get();

            foreach ($printings as $printing) {
                $lookup = MtgImageLookup::where('card_uuid', $printing->card_uuid)
                    ->whereNotNull('canonical_image_url')
                    ->first();

                if ($lookup) {
                    $imageUrl = $lookup->canonical_image_url;
                    break;
                }
            }

            // Fallback to Gatherer if needed
            if (!$imageUrl && $card->sourcePrinting && $card->sourcePrinting->multiverse_id) {
                $imageUrl = "https://gatherer.wizards.com/Pages/Card/Details.aspx?multiverseid={$card->sourcePrinting->multiverse_id}";
                Log::warning("Fallback image for normalized ID {$card->id} ({$normalizedName})");
            }

            if ($imageUrl) {
                $card->image_url_to_use = $imageUrl;
                $card->save();
            } else {
                Log::error("No image found for normalized ID {$card->id} ({$normalizedName})");
            }
        }

        $this->output->progressFinish();
        $this->info('Backfill complete.');
    }
}