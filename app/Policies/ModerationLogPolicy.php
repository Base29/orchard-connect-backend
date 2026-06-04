<?php

namespace App\Policies;

use App\Models\ModerationLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModerationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_moderation_log');
    }

    public function view(User $user, ModerationLog $moderationLog): bool
    {
        return $user->can('view_moderation_log');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ModerationLog $moderationLog): bool
    {
        return false;
    }

    public function delete(User $user, ModerationLog $moderationLog): bool
    {
        return false;
    }

    public function restore(User $user, ModerationLog $moderationLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, ModerationLog $moderationLog): bool
    {
        return false;
    }
}
