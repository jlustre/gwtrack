<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketStatus;
use App\Models\SupportWishlistItem;

class SupportDashboardMetricsService
{
    public function metrics(): array
    {
        $closedStatusIds = SupportTicketStatus::query()
            ->whereIn('slug', config('support.closed_status_slugs', []))
            ->pluck('id');

        $openTickets = SupportTicket::query()
            ->when($closedStatusIds->isNotEmpty(), fn ($query) => $query->whereNotIn('status_id', $closedStatusIds))
            ->count();

        $urgentBreaches = SupportTicket::query()
            ->where('urgency', 'urgent')
            ->where('sla_status', 'overdue')
            ->when($closedStatusIds->isNotEmpty(), fn ($query) => $query->whereNotIn('status_id', $closedStatusIds))
            ->count();

        $atRisk = SupportTicket::query()
            ->where('sla_status', 'at_risk')
            ->when($closedStatusIds->isNotEmpty(), fn ($query) => $query->whereNotIn('status_id', $closedStatusIds))
            ->count();

        $awaitingUser = SupportTicket::query()
            ->whereHas('status', fn ($query) => $query->where('slug', 'awaiting_user'))
            ->count();

        $wishlistSubmitted = SupportWishlistItem::query()->where('status', 'submitted')->count();
        $wishlistInDev = SupportWishlistItem::query()->where('status', 'in_development')->count();

        return [
            'open_tickets' => $openTickets,
            'urgent_breaches' => $urgentBreaches,
            'at_risk' => $atRisk,
            'awaiting_user' => $awaitingUser,
            'wishlist_submitted' => $wishlistSubmitted,
            'wishlist_in_development' => $wishlistInDev,
        ];
    }
}
