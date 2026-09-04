<?php

namespace App\Policies;

use App\Models\DeckOptimizerIssues as Issue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IssuePolicy
{
    protected function allow(User $user, string $permission): bool
    {
        return $user->isSuperuser() || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->allow($user, 'view_issues');
    }

    public function view(User $user, Issue $issue): bool
    {
        return $this->allow($user, 'view_issues');
    }

    public function create(User $user): bool
    {
        return $this->allow($user, 'create_issues');
    }

    public function update(User $user, Issue $issue): bool
    {
        return $this->allow($user, 'edit_issues');
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $this->allow($user, 'delete_issues');
    }

    public function restore(User $user, Issue $issue): bool
    {
        return $this->allow($user, 'restore_issues');
    }

    public function forceDelete(User $user, Issue $issue): bool
    {
        return $this->allow($user, 'force_delete_issues');
    }
}
