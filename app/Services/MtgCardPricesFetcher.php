<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MtgCardPriceFetcher
{
    public static function fetchPriceByScryfallId(string $scryfallId): ?array
    {
        $response = Http::get("https://api.scryfall.com/cards/$scryfallId");

        if ($response->failed()) return null;

        $data = $response->json();

        return [
            'usd' => $data['prices']['usd'] ?? null,
            'usd_foil' => $data['prices']['usd_foil'] ?? null,
            'retrieved_at' => now(),
        ];
    }
}
