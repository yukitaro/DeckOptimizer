<?php

namespace App\Services\Magic;

use App\Models\ListItem;
use App\Models\CardDataFromSetData;
use App\Models\CardMetadata;
use App\Models\MtgBulkPrices;

class MagicListItemAdapter
{
    /**
     * Convert a generic ListItem into a Magic-specific card profile.
     *
     * Returns a normalized structure ready for pricing, analytics, and UI.
     */
    public function toMagicCard(ListItem $item): ?array
    {
        if ($item->item_type !== 'card') {
            return null;
        }

        $cardData = CardDataFromSetData::find($item->item_id);
        if (!$cardData) {
            return null;
        }

        $meta = CardMetadata::find($cardData->card_metadata_id);
        $prices = $meta
            ? MtgBulkPrices::where('scryfall_id', $meta->scryfallId)->first()
            : null;

        $metadata = $item->metadata ?? [];

        $finish = $this->normalizeFinish($metadata['finish'] ?? null);

        return [
            'list_item_id' => $item->id,
            'card_data_id' => $cardData->id,

            // Card identity
            'name'             => $cardData->name,
            'set_name'         => $cardData->set_name,
            'number_in_set'    => $cardData->number_in_set,
            'rarity'           => $cardData->rarity,
            'colors'           => $cardData->colors,
            'color_identities' => $cardData->colorIdentities,

            // Quantity
            'quantity' => $item->quantity,

            // Metadata
            'finish'       => $finish,
            'variant'      => $metadata['variant'] ?? null,
            'condition'    => $metadata['condition'] ?? null,
            'storage'      => $metadata['storage_location'] ?? null,
            'notes'        => $metadata['notes'] ?? null,
            'purchase_price' => $metadata['purchase_price'] ?? null,
            'target_price'   => $metadata['target_price'] ?? null,

            // Scryfall metadata
            'scryfall_id' => $meta->scryfallId ?? null,
            'oracle_id'   => $meta->oracle_id ?? null,
            'image_uris'  => $meta->image_uris ?? null,

            // Market pricing
            'market_price' => $this->resolveMarketPrice($finish, $prices),

            // Full pricing breakdown
            'pricing' => $this->buildPricingBreakdown(
                $finish,
                $item->quantity,
                $metadata['purchase_price'] ?? null,
                $prices
            ),
        ];
    }

    /**
     * Normalize finish names into canonical values.
     */
    protected function normalizeFinish(?string $finish): ?string
    {
        if (!$finish) return null;

        $finish = strtolower(trim($finish));

        $map = [
            'foil'          => 'foil',
            'nonfoil'       => 'nonfoil',
            'etched'        => 'etched',
            'surge'         => 'surge',
            'halo'          => 'halo_foil',
            'halo foil'     => 'halo_foil',
            'textured'      => 'textured_foil',
            'extended'      => 'extended_art',
            'ea'            => 'extended_art',
        ];

        return $map[$finish] ?? $finish;
    }

    /**
     * Resolve the correct market price based on finish.
     */
    protected function resolveMarketPrice(?string $finish, ?MtgBulkPrices $prices): ?float
    {
        if (!$prices) return null;

        return match ($finish) {
            'foil'          => $prices->usd_foil,
            'etched'        => $prices->usd_etched,
            'halo_foil'     => $prices->usd_halo ?? $prices->usd_foil,
            'surge'         => $prices->usd_surge ?? $prices->usd_foil,
            'textured_foil' => $prices->usd_textured ?? $prices->usd_foil,
            default         => $prices->usd,
        };
    }

    /**
     * Build a full pricing breakdown for analytics.
     */
    protected function buildPricingBreakdown(
        ?string $finish,
        int $quantity,
        ?float $purchasePrice,
        ?MtgBulkPrices $prices
    ): array {
        $market = $this->resolveMarketPrice($finish, $prices);

        $purchaseTotal = $purchasePrice ? $purchasePrice * $quantity : null;
        $marketTotal   = $market ? $market * $quantity : null;

        return [
            'purchase_price' => $purchasePrice,
            'market_price'   => $market,
            'purchase_total' => $purchaseTotal,
            'market_total'   => $marketTotal,
            'profit_loss'    => ($marketTotal !== null && $purchaseTotal !== null)
                ? $marketTotal - $purchaseTotal
                : null,
        ];
    }
}
