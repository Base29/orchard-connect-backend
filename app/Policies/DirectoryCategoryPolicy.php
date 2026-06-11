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
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function view(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function create(User $user): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function update(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }

    public function delete(User $user, DirectoryCategory $directoryCategory): bool
    {
        return $user->can('verify-businesses') || $user->can('review-listings') || $user->can('manage-system');
    }
}
