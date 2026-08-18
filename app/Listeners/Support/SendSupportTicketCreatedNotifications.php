<?php

declare(strict_types=1);

namespace App\Listeners\Support;

use App\Events\Support\SupportTicketCreated;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;

class SendSupportTicketCreatedNotifications
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(SupportTicketCreated $event): void
    {
        $ticket = $event->ticket->loadMissing(['user', 'status']);

        if ($ticket->user) {
            $this->notifications->dispatch('support_ticket_created', [
                'queue' => true,
                'recipients' => [$ticket->user_id],
                'module' => 'support',
                'priority' => 'medium',
                'related' => ['type' => SupportTicket::class, 'id' => $ticket->id],
                'title' => 'Support ticket received',
                'message' => "Ticket {$ticket->ticket_number} was submitted successfully.",
                'action_link' => [
                    'route' => 'support.show',
                    'params' => ['ticket' => $ticket->id],
                    'label' => 'View ticket',
                ],
                'payload' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                ],
                'mail' => [
                    'subject' => 'Support ticket received: '.$ticket->ticket_number,
                    'greeting' => 'We received your request',
                    'lines' => [
                        'Your support ticket has been submitted successfully.',
                        'Ticket: '.$ticket->ticket_number,
                        'Subject: '.$ticket->subject,
                    ],
                    'action_text' => 'View ticket',
                    'action_url' => route('support.show', $ticket),
                ],
            ]);
        }

        if ($ticket->urgency !== 'urgent') {
            return;
        }

        User::role(['super-admin', 'admin', 'support-agent'])
            ->where('is_active', true)
            ->get()
            ->each(function (User $admin) use ($ticket): void {
                $this->notifications->dispatch('support_ticket_urgent', [
                    'queue' => true,
                    'recipients' => [$admin->id],
                    'module' => 'support',
                    'priority' => 'critical',
                    'related' => ['type' => SupportTicket::class, 'id' => $ticket->id],
                    'related_user_id' => $ticket->user_id,
                    'title' => 'Urgent support ticket',
                    'message' => "Urgent ticket {$ticket->ticket_number} was submitted by {$ticket->user?->name}.",
                    'action_link' => [
                        'route' => 'admin.support.index',
                        'params' => [],
                        'label' => 'Open admin queue',
                    ],
                    'payload' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                    ],
                    'mail' => [
                        'subject' => 'URGENT support ticket: '.$ticket->ticket_number,
                        'greeting' => 'Urgent ticket alert',
                        'lines' => [
                            'A new urgent support ticket requires immediate attention.',
                            'Ticket: '.$ticket->ticket_number,
                            'Submitted by: '.($ticket->user?->name ?? 'Unknown'),
                        ],
                        'action_text' => 'Open admin queue',
                        'action_url' => route('admin.support.index'),
                    ],
                ]);
            });
    }
}
