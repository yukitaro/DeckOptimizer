<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminPermissionController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminUserController;

use App\Http\Controllers\CollectedCardsImportController;
use App\Http\Controllers\CollectionManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\DeckScrapersController;
use App\Http\Controllers\EnumController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueEnumController;
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
use App\Models\SiteFeatures;
use App\Models\User;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::middleware('auth:sanctum')->get('/user', function () {
    $user = auth()->user()->load('roles.permissions');

    if (!$user) {
        \Log::error('No authenticated user found');
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    return response()->json($user);
});

Route::get('/sets/{cardminimum?}', function (int $cardminimum = 85) {
    return SetData::where('total_cards', '>', $cardminimum)
    ->get();
});

Route::get('/cards/name/{name}/rarities/{rarities?}', function (string $name, string $rarities = '') {
    $limit = request('limit');

    if ($rarities === '') {
        $rarities = "common,uncommon,rare,mythic";
    }

    return CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
    ->where('name', 'LIKE', "%{$name}%")
    ->limit($limit)
    ->get();
});

Route::get('/cardsfromsets/{setnames}/{rarities?}', function (string $setnames, string $rarities = '') {
    $limit = request('limit');
    $colorFilters = request('colorFilters');

    if ($rarities === '') {
        $rarities = "common,uncommon,rare,mythic";
    }

    $query = CardDataFromSetData::whereIn('rarity', explode(',', $rarities))
        ->whereIn('set_name', explode(',', "$setnames"));

    // Only apply color filtering if colorFilters is provided and not empty
    if ($colorFilters && $colorFilters !== '') {
        $query->whereIn('colors', explode(',', $colorFilters));
    }

    return $query->limit($limit)->get();
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
 
         //\Log::info("Resolved: normalized {$card->id} → sourceCard {$sourceCard->id}, metadata {$meta->id}, set {$set->id}");
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
 
         //\Log::info("Resolved: normalized {$card->id} → sourceCard {$sourceCard->id}, metadata {$meta->id}, set {$set->id}");
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

Route::middleware('auth:sanctum')->get('/collections', function () {
    $collections = CollectionManagement::with(['setsInCollection.collectedCards'])->where('owner_id', auth()->id())->get();

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


Route::middleware('auth:sanctum')->post('/collections/create', function (Request $request) {
    \Log::debug('Authenticated user ID:', ['id' => auth()->id()]);
    $name = $request->input('name');
    $description = $request->input('description', '');
    
    if (!$name) {
        return response()->json(['error' => 'Collection name is required'], 400);
    }
    
    $collection = new CollectionManagement();
    $collection->collection_name = $name;
    $collection->description = $description;
    $collection->owner_id = auth()->id();
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

Route::middleware('auth:sanctum')->get('/collections/{collection_id}/cards', function (Request $request, $collection_id) {
    $collection = CollectionManagement::where('owner_id', auth()->id())->where('id', $collection_id)->first();

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

Route::middleware('auth:sanctum')->post('/inventory/lookup-normalized', function (Request $request) {
    $names = $request->input('card_names', []);
    $collectionIds = Arr::flatten($request->input('collection_ids', []));

    if (empty($names) || empty($collectionIds)) {
        return response()->json(['error' => 'Missing card names or collection IDs'], 422);
    }

    $ownedCollectionIds = CollectionManagement::whereIn('id', $collectionIds)
        ->where('owner_id', auth()->id())
        ->pluck('id');

    if ($ownedCollectionIds->isEmpty()) {
        return response()->json(['error' => 'No matching collections found for this user'], 403);
    }

    $setIds = SetsInCollection::whereIn('collection_management_id', $ownedCollectionIds)->pluck('id');

    // ✅ Step 1: Match normalized names
    $normalizedMatches = CardDataNormalized::query()
        ->where(function ($q) use ($names) {
            foreach ($names as $name) {
                $q->orWhereRaw('LOWER(normalized_name) LIKE ?', ['%' . strtolower($name) . '%']);
            }
        })
        ->get();

    // ✅ Step 2: Resolve canonical names
    $canonicalNames = CardDataFromSetData::whereIn(
        'id',
        $normalizedMatches->pluck('source_printing_id')->filter()->unique()
    )->pluck('name')->unique();

    // ✅ Step 3: Get all printings with those names
    $allMatchingPrintings = CardDataFromSetData::whereIn('name', $canonicalNames)->pluck('id');

    // ✅ Step 4: Get collected cards matching those printings
    $cards = CollectedCardsFromSets::query()
        ->whereIn('set_in_collection_id', $setIds)
        ->whereIn('card_data_id', $allMatchingPrintings)
        ->with('cardFromSet')
        ->get();

    // ✅ Step 5: Group by normalized name
    $grouped = $normalizedMatches->map(function ($normalized) use ($cards) {
        $canonicalName = CardDataFromSetData::find($normalized->source_printing_id)?->name;

        $matchingCards = $cards->filter(fn($card) => 
            $card->cardFromSet->name === $canonicalName
        );

        return [
            'name' => $normalized->normalized_name,
            'total_count' => $matchingCards->sum('card_count'),
            'variants' => $matchingCards->map(fn($variant) => [
                'set_in_collection_id' => $variant->set_in_collection_id,
                'card_count' => $variant->card_count,
                'card_from_set' => $variant->cardFromSet,
            ]),
        ];
    })->filter(fn($entry) => $entry['total_count'] > 0);

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

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/admin/users', function () {
        return User::with('roles')->get();
    });

    Route::get('/admin/roles', [AdminRoleController::class, 'index']);
    Route::get('/admin/permissions', [AdminPermissionController::class, 'index']);
    Route::post('/admin/users/{user}/roles', [AdminUserController::class, 'assignRoles']);
    Route::post('/admin/roles/{role}/permissions', [AdminRoleController::class, 'assignPermissions']);
});

/* Route::middleware('auth:sanctum')->group(function() {
    Route::get('')
});
 */

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/enums', [EnumController::class, 'index']);
    Route::get('/issues/enums', [IssueEnumController::class, 'index']);
    Route::post('/admin/enums/{domain}', [EnumController::class, 'store'])->middleware('can:manage_enums');
    Route::post('/admin/issue-types', [IssueEnumController::class, 'store'])->middleware('can:manage_issue_types');
});


Route::middleware('auth:sanctum')->group(function() {
    Route::get('/issues', [IssueController::class, 'index']);
    Route::post('/issues', [IssueController::class, 'store']);
    Route::get('/issues/{id}', [IssueController::class, 'show']);
    Route::put('/issues/{id}', [IssueController::class, 'update']);
    Route::delete('/issues/{id}', [IssueController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/site-features', function () {
        return response()->json(SiteFeatures::orderBy('site_mode')->orderBy('sort_order')->get());
    });

    Route::get('/site-features/modes', function () {
        return response()->json(SiteFeatures::select('site_mode')->distinct()->pluck('site_mode'));
    });
});