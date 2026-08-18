<?php

declare(strict_types=1);

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
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
            ->subject('Support ticket received: '.$this->ticket->ticket_number)
            ->greeting('We received your request')
            ->line('Your support ticket has been submitted successfully.')
            ->line('Ticket: '.$this->ticket->ticket_number)
            ->line('Subject: '.$this->ticket->subject)
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'support_ticket_created',
            'category' => 'Support',
            'title' => 'Support ticket received',
            'message' => "Ticket {$this->ticket->ticket_number} was submitted successfully.",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'action_route' => 'support.show',
            'action_route_params' => ['ticket' => $this->ticket->id],
            'action_url' => route('support.show', $this->ticket, false),
        ];
    }
}
