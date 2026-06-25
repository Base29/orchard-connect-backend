<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportTicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any support tickets.
     */
    public function viewAny(User $user): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('support-staff') || $user->hasRole('marketplace-moderator')) {
            return true;
        }

        if ($user->hasRole('community-admin') && $user->can('manage-support-tickets')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the support ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('support-staff') && in_array($ticket->category, ['general', 'auth_issue', 'technical'])) {
            return true;
        }

        if ($user->hasRole('community-admin') && $ticket->category === 'security') {
            return $user->can('manage-support-tickets');
        }

        if ($user->hasRole('marketplace-moderator') && $ticket->category === 'marketplace_dispute') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create support tickets (inside Filament/admin panel).
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasAnyRole(['superadmin', 'support-staff']);
    }

    /**
     * Determine whether the user can update the support ticket.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can delete the support ticket.
     */
    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('superadmin');
    }
}
