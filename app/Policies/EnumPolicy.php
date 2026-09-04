<?php

namespace App\Policies;

use App\Models\EnumValue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EnumPolicy
{
    protected function allow(User $user, string $permission): bool
    {
        return $user->isSuperuser() || $user->hasPermission($permission);
    }

    public function create(User $user): bool
    {
        return $this->allow($user, 'manage_enums');
    }

    public function update(User $user, EnumValue $enumValue): bool
    {
        return $this->allow($user, 'manage_enums');
    }
}