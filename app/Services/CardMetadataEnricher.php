<?php

namespace App\Services;

use App\Models\CardMetadata;

class CardMetadataEnricher
{
    public static function enrich(array $cardJson, CardMetadata $metadata): void
    {
        $normalizedAttributes = [];

        if (!empty($cardJson['frameEffects'])) {
            $normalizedAttributes['frameEffects'] = $cardJson['frameEffects'];
        }

        if (!empty($cardJson['finishes'])) {
            $normalizedAttributes['finishes'] = $cardJson['finishes'];
            $metadata->hasFoil = in_array('foil', $cardJson['finishes']);
            $metadata->hasNonFoil = in_array('nonfoil', $cardJson['finishes']);
        }

        if (!empty($cardJson['identifiers'])) {
            $metadata->identifiers = $cardJson['identifiers']; // if you have this JSON column
        }

        if (!empty($cardJson['purchaseUrls'])) {
            $metadata->purchaseUrls = $cardJson['purchaseUrls']; // if you have this JSON column
        }

        $metadata->normalized_attributes = $normalizedAttributes;
        $metadata->normalized_name = static::normalizeName($cardJson['name']);
        $metadata->borderColor = $cardJson['borderColor'] ?? '';
        $metadata->frameVersion = $cardJson['frameVersion'] ?? '';
        $metadata->isFullArt = $cardJson['isFullArt'] ?? false;
    }

    private static function normalizeName(string $name): string
    {
        return preg_replace('/\s*\(.*?\)$/', '', $name); // strip suffixes
    }
}