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
        return $user->can('view_any_listing');
    }

    public function view(User $user, Listing $listing): bool
    {
        return $user->can('view_listing');
    }

    public function create(User $user): bool
    {
        return $user->can('create_listing');
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->can('update_listing');
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->can('delete_listing');
    }
}
