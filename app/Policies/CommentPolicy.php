<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->can('moderate-comments') || $user->can('override-moderation') || $user->can('manage-system');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->can('moderate-comments') || $user->can('override-moderation') || $user->can('manage-system');
    }
}
