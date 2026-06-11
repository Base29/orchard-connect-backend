<?php

namespace App\Policies;

use App\Models\ResidentProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResidentProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('verify-residents') || $user->can('override-moderation');
    }

    public function view(User $user, ResidentProfile $residentProfile): bool
    {
        return $user->can('verify-residents') || $user->can('override-moderation');
    }

    public function create(User $user): bool
    {
        return $user->can('verify-residents') || $user->can('override-moderation');
    }

    public function update(User $user, ResidentProfile $residentProfile): bool
    {
        return $user->can('verify-residents') || $user->can('override-moderation');
    }

    public function delete(User $user, ResidentProfile $residentProfile): bool
    {
        return $user->can('verify-residents') || $user->can('override-moderation');
    }
}
