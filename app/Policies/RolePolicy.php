<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

class RolePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function allow(User $user, string $permission): bool
    {
        return $user->isSuperuser() || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->allow($user, 'view_roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->allow($user, 'assign_permissions');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->allow($user, 'delete_roles');       
    }

}
