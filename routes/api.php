<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CollectedCardsImportController;
use App\Http\Controllers\CollectionManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\DeckScrapersController;
use App\Http\Controllers\MtgBulkPriceController;
use App\Http\Controllers\RetrieveCardsByBoardGroup;

use App\Builders\CollectionCardQueryBuilder;

use App\Models\CardDataFromSetData;
use App\Models\CardDataNormalized;
use App\Models\CardsInDeck;
use App\Models\CollectedCardsFromSets;
use App\Models\CollectionManagement;
use App\Models\DeckManagement;
use App\Models\InvitationToken;
use App\Models\MtgImageLookup;
use App\Models\MtgJsonImportCandidate;
use App\Models\MtgDeckBoardGroups;
use App\Models\SetData;
use App\Models\SetsInCollection;
use App\Models\User;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/cardsInDeck/{deck_id}/boardgroups/{board_groups}', [RetrieveCardsByBoardGroup::class, 'getCardsByBoardGroup']);

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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/deck', [DeckController::class, 'store']);
});

Route::middleware('auth:sanctum')->get('/decks', function () {
    $limit = request('limit');
    $retrieveRecent = request('retrieveRecent');

    if (!is_null($retrieveRecent)) {
        return DeckManagement::where('deck_owner_id', auth()->id())
            ->orWhere('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->limit((int) $retrieveRecent)
            ->get();
    }

    return DeckManagement::where('deck_owner_id', auth()->id())
        ->orWhere('visibility', 'public')
        ->limit((int) ($limit ?? 10))
        ->get();
});

Route::get('/decks/archetypes', function () {
    return DeckManagement::whereNotNull('archetype')
        ->where('archetype', '!=', '')
        ->select('archetype')
        ->distinct()
        ->pluck('archetype');
});

Route::get('/decks/known-archetypes', [DeckController::class, 'knownArchetypes']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/decks/{id}', [DeckController::class, 'destroy']);
});

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

Route::get('/collections', function () {
    $collections = CollectionManagement::with(['setsInCollection.collectedCards'])->get();

    return $collections->map(function ($collection) {

        $totalCards = $collection->setsInCollection
            ->flatMap(fn($set) => $set->collectedCards)
            ->sum('card_count');


        $total_unique_cards = $collection->setsInCollection->sum(function ($set) {
            return $set->collectedCards->count();
        });
 
        return [
            'id' => $collection->id,
            'collection_name' => $collection->collection_name,
            'description' => $collection->description,
            //'import_status' => $collection->import_status,
            'total_cards' => $totalCards,
            'total_unique_cards' => $total_unique_cards
        ];
    });
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
    
    $setIds = SetsInCollection::whereIn('collection_management_id', $collectionIds)->pluck('id');

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

    return response()->json($grouped->values());
});

Route::post('/fetch-card-prices', [MtgBulkPriceController::class, 'fetchPrices']);

Route::get('/dashboard/image-coverage', [DashboardController::class, 'imageCoverage']);

Route::get('/dashboard/data-coverage/{set_name?}', [DashboardController::class, 'dataCoverage']);

Route::get('/import-candidates', function () {
    return \App\Models\MtgJsonImportCandidate::query()
        ->where('imported_into_database', false)
        ->where('ready_for_import', true)
        ->orderByDesc('release_date')
        ->get();
});

Route::post('/import-candidates/{setCode}/import', function ($setCode) {
    Artisan::call('seed:sets', ['--sets' => $setCode]);

    \App\Models\MtgJsonImportCandidate::where('set_code', $setCode)
        ->update(['imported_into_database' => true]);

    return response()->json(['status' => 'ok']);
});

Route::get('/import-candidates/ready-count', function () {
    $count = \App\Models\MtgJsonImportCandidate::ready()->count();
    return response()->json(['ready_count' => $count]);
});

Route::get('/magic-set-data/{set_code?}', function ($set_code = null) {
    $query = \App\Models\SetData::query()
        ->where('total_cards', '>', 91);

    if ($set_code) {
        if ($set_code === 'all-released-sets') {
            $all_released_sets = $query->where('total_cards', '>', 91) // Include all sets with ARN being the smallest
                                            ->where('release_date', '<=', now())
                                            ->orderBy('release_date', 'asc')
                                            ->get();
            return $all_released_sets->map(function ($set) {
                return [
                    'id' => $set->id,
                    'set_name' => $set->set_name,
                    'set_code' => $set->official_set_code,
                    'release_date' => $set->release_date,
                    'total_cards' => $set->total_cards
                ];
            });

        }
        else {
            $query->where('set_code', $set_code);
        }
    }

    $data = $query->get();

    return response()->json($data);
});

Route::post('/deck/import-deck-from-url', [DeckScrapersController::class, 'processImportFromUrl']);

Route::get('/card/{card_name}', function($card_name) {
    return CardDataFromSetData::query()
        ->join('magic_set_data', 'card_data_from_set_data.magic_set_data_id', '=', 'magic_set_data.id')
        ->whereRaw('MATCH(card_data_from_set_data.name) AGAINST(? IN BOOLEAN MODE)', ['"' . $card_name . '"'])
        ->orderByRaw("STR_TO_DATE(magic_set_data.release_date, '%Y-%m-%d') ASC")
        ->select(
            'card_data_from_set_data.id',
            'card_data_from_set_data.name',
            'card_data_from_set_data.set_name',
            'card_data_from_set_data.number_in_set',
            'card_data_from_set_data.image_url',
            'card_data_from_set_data.slug',
            'magic_set_data.release_date'
        )
        ->get();
});

Route::get('/dashboard/card/{card_id}/metadata', [DashboardController::class, 'cardMetadata']);

Route::get('/dashboard/card/{set}/{slug}/{number}', [DashboardController::class, 'cardMetadataBySlugAndNumber']);

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $user->createToken('deckoptimizer')->plainTextToken,
        'user' => $user,
    ]);
});

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'alias' => 'nullable|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $invitation = InvitationToken::where('token', $request->invitation_token)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->firstOrFail();

    $user = User::create([
        'name' => $request->name,
        'alias' => $request->alias,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    if ($user) {
        $invitation->used = true;
        $invitation->save();
    }

    return response()->json([
        'token' => $user->createToken('deckoptimizer')->plainTextToken,
        'user' => $user,
    ]);
});

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status !== Password::RESET_LINK_SENT) {
        Log::info('Password reset link request skipped', [
            'status' => $status,
            'email_hash' => hash('sha256', strtolower((string) $request->input('email'))),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    return response()->json([
        'status' => __('passwords.sent'),
    ]);
});


Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['status' => __($status)])
        : response()->json(['error' => __($status)], 400);
});
