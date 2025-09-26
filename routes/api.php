<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeckController;
use App\Models\CardsInDeck;
use App\Models\DeckManagement;

Route::get('/cardsInDeck/{deck_id}', function ($deck_id) {
    $deck = DeckManagement::findOrFail($deck_id);

    // Step 1: Get all CardsInDeck for this deck
    $cardsInDeck = CardsInDeck::where('deck_management_id', $deck_id)->get();

    // Step 2: Build a map of card_data_normalized_id → card_count
    $countMap = $cardsInDeck->groupBy('card_data_normalized_id')->map(function ($group) {
        return $group->sum('card_count');
    });

    // Step 3: Get normalized cards via hasManyThrough
    $normalizedCards = $deck->normalizedCards;

    // Step 4: Attach card_count to each normalized card
    $enriched = $normalizedCards->map(function ($card) use ($countMap) {
        return [
            'id' => $card->id,
            'name' => $card->name,
            'type' => $card->type,
            'mana_cost' => $card->mana_cost,
            'image_url_to_use' => $card->image_url_to_use,
            'card_count' => $countMap[$card->id] ?? 0
        ];
    });

    return response()->json($enriched);
});
// ->middleware('auth:sanctum');


Route::post('/deck', [DeckController::class, 'store']);

Route::get('/decks', function () {
    $limit = request('limit');

    return DeckManagement::limit($limit)
        ->get();
});

Route::delete('/decks/{id}', [DeckController::class, 'destroy']);

Route::post('/test-image-url', function (Request $request) {
    $url = $request->input('url');
    $cardName = $request->input('card_name');
    
    if (!$url) {
        return response()->json(['error' => 'URL is required'], 400);
    }
    
    try {
        // Use Guzzle directly to get better control over redirects
        $client = new \GuzzleHttp\Client([
            'timeout' => 10,
            'allow_redirects' => [
                'max' => 10,
                'strict' => false,
                'referer' => true,
                'track_redirects' => true
            ]
        ]);
        
        $response = $client->get($url);
        
        $isBroken = false;
        $reason = '';
        $finalUrl = $url;
        $redirectChain = [];
        
        // Get the redirect history
        if ($response->hasHeader('X-Guzzle-Redirect-History')) {
            $redirectChain = $response->getHeader('X-Guzzle-Redirect-History');
        }
        
        // Get the effective/final URL
        $handlerContext = $response->getBody()->getMetadata();
        if (isset($handlerContext['effective_url'])) {
            $finalUrl = $handlerContext['effective_url'];
        }
        
        // Check if any URL in the redirect chain contains card_back
        foreach ($redirectChain as $redirectUrl) {
            if (str_contains($redirectUrl, 'card_back')) {
                $isBroken = true;
                $reason = 'Redirect to card_back.webp detected in chain';
                break;
            }
        }
        
        // Also check if the final URL contains card_back (backup check)
        if (!$isBroken && str_contains($finalUrl, 'card_back')) {
            $isBroken = true;
            $reason = 'Final URL contains card_back: ' . $finalUrl;
        }
        
        // Get content info for debugging but don't use for detection
        $contentType = $response->getHeaderLine('Content-Type');
        $contentLength = $response->getHeaderLine('Content-Length');
        
        return response()->json([
            'is_broken' => $isBroken,
            'reason' => $reason,
            'final_url' => $finalUrl,
            'original_url' => $url,
            'redirect_chain' => $redirectChain,
            'content_type' => $contentType,
            'content_length' => $contentLength,
            'status_code' => $response->getStatusCode()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'is_broken' => true,
            'reason' => 'Exception: ' . $e->getMessage(),
            'final_url' => $url,
            'original_url' => $url
        ]);
    }
});

Route::post('/report-broken-images', function (Request $request) {
    $brokenUrls = $request->input('broken_urls', []);
    
    // Store broken URLs in a simple log file for batch processing
    $logFile = storage_path('logs/broken_image_urls.json');
    $existingData = [];
    
    if (file_exists($logFile)) {
        $existingData = json_decode(file_get_contents($logFile), true) ?: [];
    }
    
    // Add new broken URLs, avoiding duplicates
    foreach ($brokenUrls as $brokenUrl) {
        $exists = false;
        foreach ($existingData as $existing) {
            if ($existing['url'] === $brokenUrl['url'] && $existing['cardName'] === $brokenUrl['cardName']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $existingData[] = [
                'url' => $brokenUrl['url'],
                'cardName' => $brokenUrl['cardName'],
                'reported_at' => now()->toISOString(),
                'fixed' => false
            ];
        }
    }
    
    file_put_contents($logFile, json_encode($existingData, JSON_PRETTY_PRINT));
    
    return response()->json([
        'message' => 'Broken URLs reported successfully',
        'count' => count($brokenUrls)
    ]);
});
