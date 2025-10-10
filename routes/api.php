<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CollectedCardsImportController;
use App\Http\Controllers\MtgBulkPriceController;
use App\Http\Controllers\CollectionManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeckController;

use App\Builders\CollectionCardQueryBuilder;

use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\CollectedCardsFromSets;
use App\Models\CollectionManagement;
use App\Models\DeckManagement;
use App\Models\MtgImageLookup;
use App\Models\MtgDeckBoardGroups;
use App\Models\SetsInCollection;

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
        $sourceCard = $card->sourceCard;
         if (!$sourceCard) {
             \Log::warning("No sourceCard for normalized ID {$card->id}");
             return null;
         }
 
         $meta = $sourceCard->cardMetadata;
         if (!$meta) {
             \Log::warning("No cardMetadata for sourceCard ID {$sourceCard->id}");
             return null;
         }
 
         $set = $sourceCard->setData;
         if (!$set) {
             \Log::warning("No setData for sourceCard ID {$sourceCard->id}");
             return null;
         }
 
         \Log::info("Resolved: normalized {$card->id} → sourceCard {$sourceCard->id}, metadata {$meta->id}, set {$set->id}");
         if (!$meta || !$set) return null;
 
         $card->name = $meta->name;
         $card->type = $meta->type;
         $card->mana_cost = $meta->mana_cost;

        $imageLookup = MtgImageLookup::where('card_uuid', $sourceCard->card_uuid)->first();
        $imageUrl = $imageLookup->canonical_image_url
            ?? $meta->image_url_to_use
            ?? $card->image_url_to_use; // fallback to existing

        return [
            'id' => $card->id,
            'image_url_to_use' => $imageUrl,
            'has_mana_cost' => !is_null($sourceCard->mana_cost),
            'name' => $sourceCard->name, //$card->name,
            'normalized_name' => $card->normalized_name,
            'scryfall_id' => $meta->scryfallId ?? null,
            'type' => $sourceCard->type, //$card->type,
            'card_count' => $countMap[$card->id] ?? 0
        ];
    });

    return response()->json($enriched);
});
// ->middleware('auth:sanctum');

Route::get('/sideboard/{deck_id}', function ($deck_id) {
    $sideboardGroup = MtgDeckBoardGroups::where('deck_id', $deck_id)
        ->where('board_type', 'side')
        ->first();

    if (!$sideboardGroup) {
        return response()->json([]);
    }

    $cardsInSideboard = $sideboardGroup->cardsInGroup;

    // Step 2: Build a map of card_data_normalized_id → card_count
    $countMap = $cardsInSideboard->groupBy('card_data_normalized_id')->map(function ($group) {
        return $group->sum('card_count');
    });

    // Step 3: Get normalized cards via hasManyThrough
    $normalizedIds = $cardsInSideboard->pluck('card_data_normalized_id')->unique();
    $normalizedCards = CardDataNormalized::whereIn('id', $normalizedIds)->get();

    // Step 4: Attach card_count to each normalized card
    $enriched = $normalizedCards->map(function ($card) use ($countMap) {
        $sourceCard = $card->sourceCard;
         if (!$sourceCard) {
             \Log::warning("No sourceCard for normalized ID {$card->id}");
             return null;
         }
 
         $meta = $sourceCard->cardMetadata;
         if (!$meta) {
             \Log::warning("No cardMetadata for sourceCard ID {$sourceCard->id}");
             return null;
         }
 
         $set = $sourceCard->setData;
         if (!$set) {
             \Log::warning("No setData for sourceCard ID {$sourceCard->id}");
             return null;
         }
 
         \Log::info("Resolved: normalized {$card->id} → sourceCard {$sourceCard->id}, metadata {$meta->id}, set {$set->id}");
         if (!$meta || !$set) return null;
 
         $card->name = $meta->name;
         $card->type = $meta->type;
         $card->mana_cost = $meta->mana_cost;

        $imageLookup = MtgImageLookup::where('card_uuid', $sourceCard->card_uuid)->first();
        $imageUrl = $imageLookup->canonical_image_url
            ?? $meta->image_url_to_use
            ?? $card->image_url_to_use; // fallback to existing

        return [
            'id' => $card->id,
            'image_url_to_use' => $imageUrl,
            'has_mana_cost' => !is_null($sourceCard->mana_cost),
            'name' => $sourceCard->name, //$card->name,
            'normalized_name' => $card->normalized_name,
            'scryfall_id' => $meta->scryfallId ?? null,
            'type' => $sourceCard->type, //$card->type,
            'card_count' => $countMap[$card->id] ?? 0
        ];
    });

    return response()->json($enriched);
});

Route::post('/deck', [DeckController::class, 'store']);

Route::get('/decks', function () {
    $limit = request('limit');

    return DeckManagement::limit($limit)
        ->get();
});

Route::delete('/decks/{id}', [DeckController::class, 'destroy']);

// Collections support
Route::delete('/collections/{id}', [CollectionManagementController::class, 'destroy']);
Route::get('/collections/{id}/export', [CollectionManagementController::class, 'export']);

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

Route::post('/collections/create', function (Request $request) {
    $name = $request->input('name');
    $description = $request->input('description', '');
    
    if (!$name) {
        return response()->json(['error' => 'Collection name is required'], 400);
    }
    
    $collection = new \App\Models\CollectionManagement();
    $collection->collection_name = $name;
    $collection->description = $description;
    $collection->owner_id = 1;
    $collection->save();
    
    return response()->json([
        'message' => 'Collection created successfully',
        'collection' => $collection
    ]);
});

Route::post('/collections/import-csv', [CollectedCardsImportController::class, 'import']);

Route::get('/collections/{id}/import-status', function ($id) {
    $collection = CollectionManagement::findOrFail($id);
    return response()->json(['status' => $collection->import_status]);
});


Route::get('/collections/{collection_id}/cards', function (Request $request, $collection_id) {
    $collection = CollectionManagement::find($collection_id);

    if (!$collection) {
        return response()->json(['error' => 'Collection not found'], 404);
    }

    $setIds = $collection->setsInCollection()->pluck('id')->toArray();

    $builder = new CollectionCardQueryBuilder($request, $setIds);
    $query = $builder->getQuery();

    $perPage = min(max((int) $request->get('per_page', 100), 1), 250);
    $page = max(1, (int) $request->get('page', 1));

    $paginator = $query->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data' => $paginator->items(),
        'meta' => [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total_matching_count' => $paginator->total(),
            'is_complete' => $paginator->lastPage() === 1
        ]
    ]);
});

Route::post('/inventory/lookup-normalized', function (Request $request) {
    $names = $request->input('card_names', []);
    $collectionIds = $request->input('collection_ids', []);
    $collectionIds = Arr::flatten($collectionIds);
    
    \Log::info('Incoming names: ' . json_encode($names));
    \Log::info('Collection IDs: ' . json_encode($collectionIds));

    // Skip CardDataNormalized entirely and go straight to the source
    $setIds = SetsInCollection::whereIn('collection_management_id', $collectionIds)->pluck('id');
    \Log::info('Set IDs: ' . json_encode($setIds->toArray()));

    // Find cards directly by name in the cardFromSet relationship
    $cards = CollectedCardsFromSets::whereIn('set_in_collection_id', $setIds)
        ->whereHas('cardFromSet', function ($q) use ($names) {
            $q->whereIn('name', $names);
        })
        ->with('cardFromSet')
        ->get();

    \Log::info('Found cards count: ' . $cards->count());

    // Group by the actual card name from the cardFromSet relationship
    $grouped = $cards->groupBy(fn($card) => $card->cardFromSet->name)
        ->map(fn($group, $name) => [
            'name' => $name,
            'total_count' => $group->sum('card_count'),
            'variants' => $group
        ]);

    \Log::info('Grouped results: ' . json_encode($grouped->keys()));
    return response()->json($grouped->values());
});

Route::post('/fetch-card-prices', [MtgBulkPriceController::class, 'fetchPrices']);

Route::get('/dashboard/image-coverage', [DashboardController::class, 'imageCoverage']);