<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ListingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Listing $listing): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->can('review-listings') || $user->can('archive-listings') || $user->can('manage-system');
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->can('review-listings') || $user->can('archive-listings') || $user->can('manage-system');
    }
}
