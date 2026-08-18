<?php

declare(strict_types=1);

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket status updated: '.$this->ticket->ticket_number)
            ->line('Your support ticket status has been updated.')
            ->line('New status: '.$this->ticket->status?->name)
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'support_ticket_status_changed',
            'category' => 'Support',
            'title' => 'Ticket status updated',
            'message' => "Ticket {$this->ticket->ticket_number} is now {$this->ticket->status?->name}.",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'status' => $this->ticket->status?->slug,
            'action_route' => 'support.show',
            'action_route_params' => ['ticket' => $this->ticket->id],
            'action_url' => route('support.show', $this->ticket, false),
        ];
    }
}
