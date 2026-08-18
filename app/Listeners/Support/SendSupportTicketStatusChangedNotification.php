<?php

declare(strict_types=1);

namespace App\Listeners\Support;

use App\Events\Support\SupportTicketStatusChanged;
use App\Models\SupportTicket;
use App\Services\Notifications\NotificationOrchestrator;

class SendSupportTicketStatusChangedNotification
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(SupportTicketStatusChanged $event): void
    {
        $ticket = $event->ticket->loadMissing(['user', 'status']);

        if ($ticket->user_id === $event->actor->id || ! $ticket->user) {
            return;
        }

        $this->notifications->dispatch('support_ticket_status_changed', [
            'queue' => true,
            'sender' => $event->actor,
            'recipients' => [$ticket->user_id],
            'module' => 'support',
            'priority' => 'medium',
            'related' => ['type' => SupportTicket::class, 'id' => $ticket->id],
            'title' => 'Ticket status updated',
            'message' => "Ticket {$ticket->ticket_number} is now {$ticket->status?->name}.",
            'action_link' => [
                'route' => 'support.show',
                'params' => ['ticket' => $ticket->id],
                'label' => 'View ticket',
            ],
            'payload' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status?->slug,
            ],
            'mail' => [
                'subject' => 'Ticket status updated: '.$ticket->ticket_number,
                'lines' => [
                    'Your support ticket status has been updated.',
                    'New status: '.($ticket->status?->name ?? 'Updated'),
                ],
                'action_text' => 'View ticket',
                'action_url' => route('support.show', $ticket),
            ],
        ]);
    }
}
