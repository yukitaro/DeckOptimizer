<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemList;
use App\Models\ListItems;
use App\Services\ListManagementService;

class ListManagementController extends Controller
{
    protected ListManagementService $service;

    public function __construct(ListManagementService $service)
    {
        $this->service = $service;
    }


    public function index(Request $request)
    {
        $userId = auth()->id();

        $lists = ItemList::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($lists);
    }
    
    /**
     * Create a new list.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'list_type'   => 'required|string',
            'is_public'   => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();

        $list = $this->service->create($validated);

        return response()->json($list, 201);
    }

    /**
     * Update an existing list.
     */
    public function update(Request $request, ItemList $list)
    {
        $validated = $request->validate([
            'name'        => 'string|max:255',
            'description' => 'nullable|string',
            'list_type'   => 'string',
            'is_public'   => 'boolean',
        ]);

        $updated = $this->service->update($list, $validated);

        return response()->json($updated);
    }

    /**
     * Delete a list and all its items.
     */
    public function destroy(ItemList $list)
    {
        $this->service->delete($list);

        return response()->json(['deleted' => true]);
    }

    /**
     * Add an item to a list.
     */
    public function addItem(Request $request, ItemList $list)
    {
        $validated = $request->validate([
            'item_type' => 'required|string',
            'item_id'   => 'required|integer',
            'quantity'  => 'integer|min:1',
            'metadata'  => 'array',
        ]);

        $item = $this->service->addItem($list, $validated);

        return response()->json($item, 201);
    }

    /**
     * Update a list item.
     */
    public function updateItem(Request $request, ListItems $item)
    {
        $validated = $request->validate([
            'item_type' => 'string',
            'item_id'   => 'integer',
            'quantity'  => 'integer|min:1',
            'metadata'  => 'array',
        ]);

        $updated = $this->service->updateItem($item, $validated);

        return response()->json($updated);
    }

    /**
     * Delete a list item.
     */
    public function deleteItem(ListItems $item)
    {
        $this->service->deleteItem($item);

        return response()->json(['deleted' => true]);
    }

    /**
     * Get a list with all items.
     */
    public function show(ItemList $list)
    {
        return ItemList::with([
            'items',
            'items.card',
            'items.card.cardMetadata',
            'items.card.cardMetadata.prices'
        ])->findOrFail($list->id);
    }

    public function addCard(Request $request)
    {
        $request->validate([
            'list_name' => 'required|string',
            'card_id'   => 'required|integer|exists:card_data_from_set_data,id',
            'is_foil'   => 'boolean'
        ]);

        $list = ItemList::firstOrCreate(
            [
                'name'      => $request->list_name,
                'user_id'   => auth()->id(),
                'list_type' => 'mtg_cards',
            ],
            [
                'description' => null,
                'is_public'   => false,
            ]
        );

        $item = $list->items()->updateOrCreate(
            [
                'item_type' => 'card',
                'item_id'   => $request->card_id,
            ],
            [
                'metadata' => [
                    'is_foil' => (bool) $request->is_foil,
                    'quantity' => 1,
                ],
            ]
        );

        return [
            'success' => true,
            'list_id' => $list->id,
            'item_id' => $item->id,
        ];
    }
}
