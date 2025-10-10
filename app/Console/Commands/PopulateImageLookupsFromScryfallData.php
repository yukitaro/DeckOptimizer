<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Models\CardDataFromSetData;
use App\Models\MtgImageLookup;


class PopulateImageLookupsFromScryfallData extends Command
{
    protected $signature = 'app:seed-image-lookups-from-scryfall';

    protected $description = 'Seed mtg_image_lookups for cards missing image_url using Scryfall bulk data';

    public function handle()
    {
        $this->info('Seeding image lookups for cards missing image_url…');

        // Find latest bulk file
        $path = collect(File::files(storage_path('app/scryfall')))
            ->filter(fn($f) => str_contains($f->getFilename(), 'default_cards'))
            ->sortByDesc(fn($f) => $f->getCTime())
            ->first()?->getPathname();

        if (!$path || !file_exists($path)) {
            $this->error("No bulk data file found in storage/app/scryfall");
            return;
        }

        $bulkCards = json_decode(file_get_contents($path), true);
        if (!is_array($bulkCards)) {
            $this->error("Failed to parse bulk data JSON.");
            return;
        }

        // Get scryfallIds for cards missing image_url
        $missingIds = CardDataFromSetData::whereNull('image_url')
            ->whereNotNull('card_metadata_id')
            ->join('card_metadata', 'card_data_from_set_data.card_metadata_id', '=', 'card_metadata.id')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(card_metadata.identifiers, '$.scryfallId')) IS NOT NULL")
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(card_metadata.identifiers, '$.scryfallId')) as scryfall_id")
            ->get()
            ->pluck('scryfall_id')
            ->unique()
            ->toArray();

        $this->info('Missing image cards with scryfallId: ' . count($missingIds));


        // Build lookup map for O(1) filtering
        $missingIdMap = array_flip($missingIds);

        // Filter bulk cards to only those we need
        $relevantCards = array_filter($bulkCards, function ($card) use ($missingIdMap) {
            return isset($missingIdMap[$card['id'] ?? '']);
        });

        // Seed mtg_image_lookups only for relevant cards
        $count = 0;
        foreach ($relevantCards as $card) {
            $scryfallId = $card['id'];

            MtgImageLookup::updateOrCreate(
                ['card_uuid' => $scryfallId],
                [
                    'original_image_url' => $card['image_uris']['normal'] ?? null,
                    'canonical_image_url' => $card['image_uris']['normal'] ?? null,
                    'canonical_image_url_back' => $card['card_faces'][1]['image_uris']['normal'] ?? null,
                    'scryfall_image_uris' => $card['image_uris'] ?? null,
                    'hydrated_via_command' => true,
                ]
            );

            $count++;
        }

        $this->info("✅ Seeded $count image lookups.");

        // Bulk update card_data_from_set_data using indexed scryfall_id
        $this->info("Hydrating image_url in card_data_from_set_data…");

        if (!empty($missingIds)) {
            DB::table('card_data_from_set_data as cd')
                ->join('card_metadata as cm', 'cd.card_metadata_id', '=', 'cm.id')
                ->join('mtg_image_lookups as il', 'cm.scryfall_id', '=', 'il.card_uuid')
                ->whereNull('cd.image_url')
                ->whereIn('cm.scryfall_id', $missingIds)
                ->update([
                    'cd.image_url' => DB::raw('il.canonical_image_url'),
                ]);
        }

        $this->info("✅ Bulk hydration complete.");
    }
}
