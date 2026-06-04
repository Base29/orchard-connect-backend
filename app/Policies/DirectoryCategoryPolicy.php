<?php

namespace App\Policies;

use App\Models\DirectoryCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DirectoryCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_directory_category');
    }

    public function view(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('view_directory_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_directory_category');
    }

    public function update(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('update_directory_category');
    }

    public function delete(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('delete_directory_category');
    }
}
