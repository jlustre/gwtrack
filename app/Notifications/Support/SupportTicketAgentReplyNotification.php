<?php

declare(strict_types=1);

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketAgentReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly SupportTicketComment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Support replied: '.$this->ticket->ticket_number)
            ->line('A support agent replied to your ticket.')
            ->line(\Illuminate\Support\Str::limit($this->comment->body, 180))
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'support_ticket_agent_reply',
            'category' => 'Support',
            'title' => 'Support team replied',
            'message' => "New reply on ticket {$this->ticket->ticket_number}.",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'action_route' => 'support.show',
            'action_route_params' => ['ticket' => $this->ticket->id],
            'action_url' => route('support.show', $this->ticket, false),
        ];
    }
}
