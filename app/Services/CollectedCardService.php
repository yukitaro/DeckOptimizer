<?php

namespace App\Services;

use App\Models\CollectedCardsFromSets;

class CollectedCardService {
    public static function handle(string $mode, array $attributes): void {

        switch ($mode) {
            case 'merge':
                static::merge($attributes);
                break;
            case 'set':
                static::set($records);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported import mode: {$mode}");
            break;
        }
    }

    protected static function merge(array $attributes) {
        $attributes['condition'] = $attributes['condition'] ?: null;
        $attributes['printing_variant'] = $attributes['printing_variant'] ?: null;
        $attributes['purchase_price'] = $attributes['purchase_price'] ?: null;
        $attributes['storage_location'] = $attributes['storage_location'] ?: null;

        $query = CollectedCardsFromSets::query()
            ->where('set_in_collection_id', $attributes['set_in_collection_id'])
            ->where('card_data_id', $attributes['card_data_id'])
            ->where('is_foil', $attributes['is_foil']);

        // Handle nullable fields explicitly
        $nullableFields = ['condition', 'printing_variant', 'purchase_price', 'storage_location'];

        foreach ($nullableFields as $field) { $attributes[$field] = $attributes[$field] ?: null; }

        $match = $query->first();

        if ($match) {
            // Update existing record
            $match->increment('card_count', $attributes['card_count']);
            $match->save();
        } else {
            // Create new record
            CollectedCardsFromSets::create($attributes);
        }
    }
}
