<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Post $post): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('moderate-comments') || $user->can('override-moderation') || $user->can('manage-system');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('moderate-comments') || $user->can('override-moderation') || $user->can('manage-system');
    }
}
