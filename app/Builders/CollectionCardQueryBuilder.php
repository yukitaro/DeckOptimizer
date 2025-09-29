<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\CollectedCardsFromSets;
use App\Models\CardDataFromSetData;

class CollectionCardQueryBuilder
{
    protected Builder $query;

    public function __construct(Request $request, array $setIds)
    {
        $matchingCardIds = [];

        if ($request->filled('search')) {
            $matchingCardIds = CardDataFromSetData::query()
                ->where('name', 'like', '%' . $request->search . '%')
                ->pluck('id')
                ->toArray();
        }

        $this->query = CollectedCardsFromSets::query()
            ->whereIn('set_in_collection_id', $setIds)
            ->with('cardFromSet');

        if (!empty($matchingCardIds)) {
            $this->query->whereIn('card_data_id', $matchingCardIds);
        }

        $this->applyFilters($request);
        $this->applySorting($request);
    }

    protected function applyFilters(Request $request): void
    {
        if ($request->filled('sets')) {
            $setCodes = explode(',', $request->sets);
            $this->query->whereHas('cardFromSet', function ($q) use ($setCodes) {
                $q->whereIn('set_code', $setCodes);
            });
        }

        if ($request->filled('colors')) {
            $colors = explode(',', $request->colors);
            $this->query->whereHas('cardFromSet', function ($q) use ($colors) {
                foreach ($colors as $color) {
                    $q->orWhereJsonContains('colors', $color);
                }
            });
        }

        if ($request->filled('rarities')) {
            $rarities = explode(',', $request->rarities);
            $this->query->whereHas('cardFromSet', function ($q) use ($rarities) {
                $q->whereIn('rarity', $rarities);
            });
        }
    }

    protected function applySorting(Request $request): void
    {
        if ($request->sort === 'name') {
            // Sort after retrieval or use a join if needed
            $this->query->orderBy('card_data_id'); // fallback sort
        } else {
            // Default sort by card count descending
            $this->query->orderBy('card_count', 'desc');
        }
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }
}