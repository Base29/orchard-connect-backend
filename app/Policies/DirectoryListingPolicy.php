<?php

namespace App\Policies;

use App\Models\DirectoryListing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DirectoryListingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_directory_listing');
    }

    public function view(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('view_directory_listing');
    }

    public function create(User $user): bool
    {
        return $user->can('create_directory_listing');
    }

    public function update(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('update_directory_listing');
    }

    public function delete(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('delete_directory_listing');
    }
}
