<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-system') || $user->can('create-permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('manage-system') || $user->can('create-permissions');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-system') || $user->can('create-permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('manage-system') || $user->can('create-permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('manage-system') || $user->can('create-permissions');
    }
}
