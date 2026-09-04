<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ScryfallImageHydratorService
{
    public function seedImageLookups(array $onlySets = [], bool $force = false): int
    {
        DB::connection()->disableQueryLog();

        $path = collect(File::files(storage_path('app/scryfall')))
            ->filter(fn($f) => str_contains($f->getFilename(), 'default-cards') && str_ends_with($f->getFilename(), '.jsonl'))
            ->sortByDesc(fn($f) => $f->getCTime())
            ->first()?->getPathname();

        if (!$path || !file_exists($path)) {
            throw new \RuntimeException("No bulk data file found.");
        }

        $insertData = [];
        $inserted = 0;
        $fh = fopen($path, 'r');

        while (($line = fgets($fh)) !== false) {
            $card = json_decode(trim($line), true);
            if (!$card) continue;

            if (!empty($onlySets) && !in_array(strtoupper($card['set'] ?? ''), $onlySets)) {
                continue;
            }

            $scryfallId = $card['id'] ?? null;
            if (!$scryfallId) continue;

            $frontImage = $card['image_uris']['normal']
                ?? $card['card_faces'][0]['image_uris']['normal']
                ?? null;
            $backImage = $card['card_faces'][1]['image_uris']['normal'] ?? null;

            if (!$force && !$frontImage) continue;

            $insertData[] = [
                'card_uuid'                => $scryfallId,
                'original_image_url'       => $frontImage,
                'canonical_image_url'      => $frontImage,
                'canonical_image_url_back' => $backImage,
                'scryfall_image_uris'      => json_encode($card['image_uris'] ?? null),
                'hydrated_via_command'     => true,
                'created_at'               => now(),
                'updated_at'               => now(),
            ];

            if (count($insertData) >= 1000) {
                DB::table('mtg_image_lookups')->upsert(
                    $insertData,
                    ['card_uuid'],
                    ['original_image_url', 'canonical_image_url', 'canonical_image_url_back', 'scryfall_image_uris', 'hydrated_via_command', 'updated_at']
                );
                $inserted += count($insertData);
                $insertData = [];
            }
        }
        fclose($fh);

        if (!empty($insertData)) {
            DB::table('mtg_image_lookups')->upsert(
                $insertData,
                ['card_uuid'],
                ['original_image_url', 'canonical_image_url', 'canonical_image_url_back', 'scryfall_image_uris', 'hydrated_via_command', 'updated_at']
            );
            $inserted += count($insertData);
        }

        return $inserted;
    }

    public function hydrateLinkedTables(array $onlySets = [], bool $force = false): array
    {
        DB::connection()->disableQueryLog();

        $updated = 0;
        $normalizedUpdated = 0;

        $whereClause = $force
            ? "WHERE cm.scryfall_id IS NOT NULL"
            : "WHERE cm.scryfall_id IS NOT NULL AND (cds.image_url IS NULL OR cds.image_url != mil.canonical_image_url)";

        if (!empty($onlySets)) {
            $setList = "'" . implode("','", $onlySets) . "'";
            $whereClause .= " AND cds.set_name IN ({$setList})";
        }

        $updated = DB::update("
            UPDATE card_data_from_set_data cds
            INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
            INNER JOIN mtg_image_lookups mil ON mil.card_uuid = cm.scryfall_id
            SET cds.image_url = mil.canonical_image_url,
                cds.image_normalized_at = NOW()
            {$whereClause}
        ");

        $normWhere = $force
            ? "WHERE cm.scryfall_id IS NOT NULL"
            : "WHERE cm.scryfall_id IS NOT NULL AND (cdn.image_url_to_use IS NULL OR cdn.image_url_to_use != mil.canonical_image_url)";

        if (!empty($onlySets)) {
            $setList = "'" . implode("','", $onlySets) . "'";
            $normWhere .= " AND cds.set_name IN ({$setList})";
        }

        $normalizedUpdated = DB::update("
            UPDATE card_data_normalized cdn
            INNER JOIN card_data_from_set_data cds ON cdn.source_printing_id = cds.id
            INNER JOIN card_metadata cm ON cds.card_metadata_id = cm.id
            INNER JOIN mtg_image_lookups mil ON mil.card_uuid = cm.scryfall_id
            SET cdn.image_url_to_use = mil.canonical_image_url
            {$normWhere}
        ");

        return [
            'updated' => $updated,
            'normalizedUpdated' => $normalizedUpdated,
        ];
    }
}