<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('create-news') || $user->can('manage-system');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->can('create-news') || $user->can('manage-system');
    }

    public function create(User $user): bool
    {
        return $user->can('create-news') || $user->can('manage-system');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can('create-news') || $user->can('manage-system');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can('create-news') || $user->can('manage-system');
    }
}
