<?php

namespace App\Services;

use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;

class VariantCardResolver
{
    public static function resolveCardData(string $rawName, string $setCode): ?CardDataFromSetData
    {
        $metadata = self::resolveMetadata($rawName, $setCode);
        if (!$metadata) {
            return null;
        }
        
        // Return the card from the specific set, not just any card with this metadata
        return CardDataFromSetData::where('card_metadata_id', $metadata->id)
            ->where('set_name', $setCode)  // This is the missing constraint!
            ->first();
    }

    public static function resolveMetadata(string $rawName, string $setCode): ?CardMetadata
    {
        $parsed = self::parseName($rawName);
        $filters = self::getVariantFilters($parsed['variant_tag']);

        $metadataIds = CardDataFromSetData::where('set_name', $setCode)
            ->whereNotNull('card_metadata_id')
            ->pluck('card_metadata_id');

        $query = CardMetadata::where('normalized_name', $parsed['base_name'])
            ->whereIn('id', $metadataIds);

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $query->where(fn($q) => $q->whereNull("normalized_attributes->$key")->orWhereJsonLength("normalized_attributes->$key", 0));
                } else {
                    $query->whereJsonContains("normalized_attributes->$key", $value);
                }
            } else {
                $query->where("normalized_attributes->$key", $value);
            }
        }

        return $query->first();
    }

    public static function getVariantFilters(string $variantTag): array
    {
        return match ($variantTag) {
            'Extended Art' => ['frameEffects' => ['extendedart']],
            'Borderless' => ['borderColor' => 'borderless', 'isFullArt' => true, 'frameEffects' => []],
            'Showcase' => ['frameEffects' => ['showcase']],
            'Retro Frame' => ['frameVersion' => '1997', 'borderColor' => 'black'],
            'Foil Etched' => ['finishes' => ['foil', 'etched']],
            'Alternate Art' => ['frameEffects' => ['inverted'], 'borderColor' => 'borderless', 'isFullArt' => true],
            default => [],
        };
    }

    public static function parseName(string $rawName): array
    {
        preg_match('/^(.*?)\s*\((.*?)\)$/', $rawName, $matches);
        return [
            'base_name' => trim($matches[1] ?? $rawName),
            'variant_tag' => trim($matches[2] ?? ''),
        ];
    }
}