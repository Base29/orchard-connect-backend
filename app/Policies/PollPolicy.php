<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PollPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Poll $poll): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id || $user->can('manage-polls') || $user->can('manage-system');
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id || $user->can('manage-polls') || $user->can('manage-system');
    }
}
