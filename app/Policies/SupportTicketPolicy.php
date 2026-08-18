<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\SupportWishlistItem;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view own support tickets') || $user->can('view all support tickets');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        if ($user->can('view all support tickets')) {
            return true;
        }

        return $user->can('view own support tickets') && $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('submit support ticket');
    }

    public function comment(User $user, SupportTicket $ticket): bool
    {
        if ($user->can('manage support tickets')) {
            return true;
        }

        return $user->can('comment on own ticket') && $ticket->user_id === $user->id;
    }

    public function reopen(User $user, SupportTicket $ticket): bool
    {
        if ($user->can('manage support tickets')) {
            return true;
        }

        return $user->can('reopen own ticket')
            && $ticket->user_id === $user->id
            && $ticket->isClosed();
    }

    public function manage(User $user): bool
    {
        return $user->can('manage support tickets');
    }

    public function assign(User $user): bool
    {
        return $user->can('assign support tickets');
    }

    public function updateStatus(User $user): bool
    {
        return $user->can('update support ticket status');
    }

    public function addInternalNote(User $user): bool
    {
        return $user->can('add internal support notes');
    }

    public function voteWishlist(User $user, SupportWishlistItem $item): bool
    {
        return $user->can('vote on wishlist items');
    }

    public function manageWishlist(User $user): bool
    {
        return $user->can('manage enhancement wishlist');
    }
}
