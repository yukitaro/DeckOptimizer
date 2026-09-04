<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    protected function allow(User $user, string $permission): bool
    {
        return $user->isSuperuser() || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->allow($user, 'view_users');
    }

    public function view(User $user, User $target): bool
    {
        return $this->allow($user, 'view_users');
    }

    public function update(User $user, User $target): bool
    {
        return $this->allow($user, 'assign_roles');
    }

    public function delete(User $user, User $target): bool
    {
        return $this->allow($user, 'delete_users');       
    }
}
