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
        return $user->can('view_any_permission');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view_permission');
    }

    public function create(User $user): bool
    {
        return $user->can('create_permission');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('update_permission');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete_permission');
    }
}
