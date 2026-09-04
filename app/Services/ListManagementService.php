<?php

namespace App\Services;

use App\Models\ItemList;
use App\Models\ListItem;

class ListManagementService
{
    public function create(array $data): ItemList
    {
        return ItemList::create($data);
    }

    public function update(ItemList $list, array $data): ItemList
    {
        $list->update($data);
        return $list;
    }

    public function delete(ItemList $list): void
    {
        $list->items()->delete();
        $list->delete();
    }

    public function addItem(ItemList $list, array $data): ListItem
    {
        return $list->items()->create($data);
    }

    public function updateItem(ListItem $item, array $data): ListItem
    {
        $item->update($data);
        return $item;
    }

    public function deleteItem(ListItem $item): void
    {
        $item->delete();
    }

    public function getListWithItems(int $id): ?ItemList
    {
        return ItemList::with('items')->find($id);
    }
}