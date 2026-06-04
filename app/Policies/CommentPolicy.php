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
        return $user->can('view_any_comment');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->can('view_comment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comment');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->can('update_comment');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->can('delete_comment');
    }
}
