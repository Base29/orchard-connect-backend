<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-system') || $user->can('create-roles') || $user->can('assign-admin-roles') || $user->can('assign-moderator-roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('manage-system') || $user->can('create-roles') || $user->can('assign-admin-roles') || $user->can('assign-moderator-roles');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-system') || $user->can('create-roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('manage-system') || $user->can('create-roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('manage-system') || $user->can('create-roles');
    }
}
