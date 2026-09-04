<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MTGCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'set_name' => $this->set_name,
            'slug' => $this->slug,
            'set_code' => $this->set_code,
            'number_in_set' => $this->number_in_set,
            'rarity' => $this->rarity,
            'colors' => $this->colors,
            'mana_cost' => $this->mana_cost,
            'text' => $this->text,
            'type' => $this->type,
            'image_url' => $this->image_url,
            'release_date' => $this->when($this->relationLoaded('cardMetadata') &&
                                          $this->cardMetadata?->relationLoaded('prices'),
                                          fn () => $this->cardMetadata?->prices?->released_at),
            'has_foil' => (bool) ($this->cardMetadata?->hasFoil() ?? false),
            'has_nonfoil' => (bool) ($this->cardMetadata?->hasNonfoil() ?? false),

            // Metadata
            'scryfall_id' => $this->when(
                $this->relationLoaded('cardMetadata'),
                fn () => $this->cardMetadata?->scryfallId
            ),

            // Prices
            'prices' => $this->when(
                $this->relationLoaded('cardMetadata') &&
                $this->cardMetadata?->relationLoaded('prices'),
                fn () => [
                    'usd' => $this->cardMetadata?->prices?->usd,
                    'usd_foil' => $this->cardMetadata?->prices?->usd_foil,
                    'updated_at' => $this->cardMetadata?->prices?->updated_at,
                ]
            ),

            // Collection counts
            'copies_owned' => $this->whenLoaded('collectedCards', function () {
                return $this->collectedCards->sum('card_count');
            }),
        ];
    }
}
