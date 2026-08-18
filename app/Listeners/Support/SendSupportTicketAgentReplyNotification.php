<?php

declare(strict_types=1);

namespace App\Listeners\Support;

use App\Events\Support\SupportTicketAgentReplied;
use App\Models\SupportTicket;
use App\Services\Notifications\NotificationOrchestrator;

class SendSupportTicketAgentReplyNotification
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(SupportTicketAgentReplied $event): void
    {
        $ticket = $event->ticket->loadMissing('user');

        if ($ticket->user_id === $event->agent->id || ! $ticket->user) {
            return;
        }

        $this->notifications->dispatch('support_ticket_agent_reply', [
            'queue' => true,
            'sender' => $event->agent,
            'recipients' => [$ticket->user_id],
            'module' => 'support',
            'priority' => 'medium',
            'related' => ['type' => SupportTicket::class, 'id' => $ticket->id],
            'title' => 'Support team replied',
            'message' => "New reply on ticket {$ticket->ticket_number}.",
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
                'subject' => 'Support replied: '.$ticket->ticket_number,
                'lines' => [
                    'A support agent replied to your ticket.',
                    \Illuminate\Support\Str::limit($event->comment->body, 180),
                ],
                'action_text' => 'View ticket',
                'action_url' => route('support.show', $ticket),
            ],
        ]);
    }
}
