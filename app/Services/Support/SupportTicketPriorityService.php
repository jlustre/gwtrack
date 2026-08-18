<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class SupportTicketPriorityService
{
    public function calculateScore(
        string $urgency,
        string $impact,
        string $frequency = 'unknown',
        ?string $wishlistUserPriority = null,
        ?string $developmentComplexity = null,
    ): int {
        $urgencyWeight = config('support.urgency_weights.'.$urgency, 10);
        $impactWeight = config('support.impact_weights.'.$impact, 5);
        $frequencyWeight = config('support.frequency_weights.'.$frequency, 4);
        $valueWeight = $wishlistUserPriority
            ? (int) config('support.wishlist_value_weights.'.$wishlistUserPriority, 5)
            : 0;
        $complexityWeight = $developmentComplexity
            ? (int) config('support.complexity_weights.'.$developmentComplexity, 0)
            : 0;

        return max(0, $urgencyWeight + $impactWeight + $frequencyWeight + $valueWeight - $complexityWeight);
    }

    public function urgencySqlCase(): string
    {
        return "CASE urgency
            WHEN 'urgent' THEN 40
            WHEN 'high' THEN 30
            WHEN 'medium' THEN 20
            ELSE 10
        END";
    }

    public function impactSqlCase(): string
    {
        return "CASE impact
            WHEN 'all' THEN 30
            WHEN 'agency' THEN 25
            WHEN 'team' THEN 20
            WHEN 'trainee' THEN 15
            ELSE 10
        END";
    }

    public function frequencySqlCase(): string
    {
        return "CASE frequency
            WHEN 'always' THEN 20
            WHEN 'sometimes' THEN 12
            WHEN 'once' THEN 6
            ELSE 4
        END";
    }

    public function applyPriorityOrdering($query)
    {
        $urgency = $this->urgencySqlCase();
        $impact = $this->impactSqlCase();
        $frequency = $this->frequencySqlCase();

        return $query
            ->selectRaw("support_tickets.*, ({$urgency} + {$impact} + {$frequency}) as computed_priority_score")
            ->orderByDesc('computed_priority_score')
            ->orderByDesc('support_tickets.created_at');
    }

    public function refreshTicketScore(SupportTicket $ticket): void
    {
        $ticket->update([
            'priority_score' => $this->calculateScore(
                $ticket->urgency,
                $ticket->impact,
                $ticket->frequency,
            ),
        ]);
    }

    public function refreshWishlistScore(\App\Models\SupportWishlistItem $item): void
    {
        $item->update([
            'admin_priority_score' => $this->calculateScore(
                urgency: match ($item->user_priority) {
                    'high' => 'high',
                    'medium' => 'medium',
                    default => 'low',
                },
                impact: 'team',
                frequency: 'sometimes',
                wishlistUserPriority: $item->user_priority,
                developmentComplexity: $item->development_complexity,
            ) + ($item->votes()->count() * 2),
        ]);
    }
}
