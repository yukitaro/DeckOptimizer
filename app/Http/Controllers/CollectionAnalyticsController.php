<?php

namespace App\Http\Controllers;

use App\Services\CollectionAnalyticsService;

class CollectionAnalyticsController extends Controller
{
    public function __construct(
        private readonly CollectionAnalyticsService $service
    ) {}

    public function summary(int $id)
    {
        return response()->json($this->service->valueSummary($id));
    }

    public function sets(int $id)
    {
        return response()->json($this->service->setsBreakdown($id));
    }

    public function rarity(int $id)
    {
        return response()->json($this->service->rarityBreakdown($id));
    }

    public function foil(int $id)
    {
        return response()->json($this->service->foilBreakdown($id));
    }

    public function top(int $id)
    {
        return response()->json($this->service->topCards($id));
    }

    public function colors(int $id)
    {
        return response()->json($this->service->colorIdentityBreakdown($id));
    }
}
