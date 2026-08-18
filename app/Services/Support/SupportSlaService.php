<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportSlaPolicy;
use App\Models\SupportTicket;
use App\Models\SupportTicketStatus;
use Illuminate\Support\Collection;

class SupportSlaService
{
    public function checkTicket(SupportTicket $ticket): void
    {
        $ticket->loadMissing('status');

        if ($ticket->isClosed()) {
            return;
        }

        $policy = SupportSlaPolicy::query()->where('urgency', $ticket->urgency)->first();

        if (! $policy) {
            return;
        }

        $hoursElapsed = (int) $ticket->created_at->diffInHours(now());
        $responseHours = (int) $policy->response_time_hours;
        $atRiskThreshold = max(1, (int) floor($responseHours * 0.75));

        if ($hoursElapsed >= $responseHours) {
            if ($ticket->sla_status !== 'overdue') {
                $ticket->update(['sla_status' => 'overdue']);
            }

            return;
        }

        if ($hoursElapsed >= $atRiskThreshold && $ticket->sla_status === 'on_track') {
            $ticket->update(['sla_status' => 'at_risk']);
        }
    }

    public function checkAllOpenTickets(): int
    {
        $closedIds = SupportTicketStatus::query()
            ->whereIn('slug', config('support.closed_status_slugs', []))
            ->pluck('id');

        $updated = 0;

        SupportTicket::query()
            ->when($closedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('status_id', $closedIds))
            ->chunkById(100, function (Collection $tickets) use (&$updated): void {
                foreach ($tickets as $ticket) {
                    $before = $ticket->sla_status;
                    $this->checkTicket($ticket);
                    $ticket->refresh();

                    if ($ticket->sla_status !== $before) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
