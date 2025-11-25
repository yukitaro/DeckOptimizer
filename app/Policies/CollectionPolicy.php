<?php

namespace App\Policies;

class CollectionPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, CollectionManagement $collection)
    {
        return $user->id === $collection->owner_id
            || $collection->delegates->contains($user->id);
    }

    public function update(User $user, CollectionManagement $collection)
    {
        return $collection->delegates()
            ->wherePivot('can_edit', true)
            ->where('user_id', $user->id)
            ->exists();
    }
}