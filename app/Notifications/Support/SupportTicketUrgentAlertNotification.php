<?php

declare(strict_types=1);

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUrgentAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENT support ticket: '.$this->ticket->ticket_number)
            ->greeting('Urgent ticket alert')
            ->line('A new urgent support ticket requires immediate attention.')
            ->line('Ticket: '.$this->ticket->ticket_number)
            ->line('Submitted by: '.$this->ticket->user?->name)
            ->action('Open admin queue', route('admin.support.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'support_ticket_urgent',
            'category' => 'Support',
            'title' => 'Urgent support ticket',
            'message' => "Urgent ticket {$this->ticket->ticket_number} was submitted by {$this->ticket->user?->name}.",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'action_route' => 'admin.support.index',
            'action_route_params' => [],
            'action_url' => route('admin.support.index', [], false),
        ];
    }
}
