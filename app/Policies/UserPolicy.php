<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-system') || $user->can('assign-admin-roles') || $user->can('assign-moderator-roles');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('manage-system') || $user->can('assign-admin-roles') || $user->can('assign-moderator-roles');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-system') || $user->can('assign-admin-roles');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage-system') || $user->can('assign-admin-roles');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('manage-system') || $user->can('assign-admin-roles');
    }
}
