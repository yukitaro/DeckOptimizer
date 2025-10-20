<?php

namespace App\Services;

use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;

class VariantCardResolver
{
    public static function resolveCardData(string $rawName, string $setCode): ?CardDataFromSetData
    {
        $parsed = self::parseName($rawName);
        $nameLower = mb_strtolower($parsed['base_name']);

        // Direct match by name within the set (try both columns)
        $direct = CardDataFromSetData::query()
            ->whereRaw('LOWER(name) = ?', [$nameLower])
            ->where(function ($q) use ($setCode) {
                $q->where('set_name', $setCode);
            })
            ->first();

        if ($direct) {
            return $direct;
        }

        // Fallback to metadata-driven resolution
        $metadata = self::resolveMetadata($rawName, $setCode);
        if (!$metadata) {
            return null;
        }

        return CardDataFromSetData::query()
            ->where('card_metadata_id', $metadata->id)
            ->where(function ($q) use ($setCode) {
                $q->where('set_name', $setCode);
            })
            ->first();
    }

    public static function resolveMetadata(string $rawName, string $setCode): ?CardMetadata
    {
        $parsed = self::parseName($rawName);
        $filters = self::getVariantFilters($parsed['variant_tag']);

        $metadataIds = CardDataFromSetData::query()
            ->whereNotNull('card_metadata_id')
            ->where(function ($q) use ($setCode) {
                $q->where('set_name', $setCode);
            })
            ->pluck('card_metadata_id');

        if ($metadataIds->isEmpty()) {
            return null;
        }

        $query = CardMetadata::where('normalized_name', $parsed['base_name'])
            ->whereIn('id', $metadataIds);

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $query->where(fn($q) =>
                        $q->whereNull("normalized_attributes->$key")
                        ->orWhereJsonLength("normalized_attributes->$key", 0)
                    );
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