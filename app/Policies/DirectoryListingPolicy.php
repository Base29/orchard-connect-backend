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
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function view(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function create(User $user): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function update(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function delete(User $user, DirectoryListing $directoryListing): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }
}
