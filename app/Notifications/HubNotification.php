<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic hub notification used as a Laravel Notifications adapter.
 * The orchestrator primarily writes via NotificationService; this class
 * supports mail/database delivery through Laravel's notification pipeline.
 */
class HubNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $channels
     * @param  array<string, mixed>|null  $mail
     */
    public function __construct(
        private readonly array $payload,
        private readonly array $channels = ['database'],
        private readonly ?array $mail = null,
    ) {}

    public function via(object $notifiable): array
    {
        return collect($this->channels)
            ->map(fn (string $channel) => match ($channel) {
                'in_app', 'database' => 'database',
                'email', 'mail' => 'mail',
                default => $channel,
            })
            ->unique()
            ->values()
            ->all();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mail ?? [];

        $message = (new MailMessage)
            ->subject($mail['subject'] ?? ($this->payload['title'] ?? 'Notification'));

        if (! empty($mail['greeting'])) {
            $message->greeting($mail['greeting']);
        }

        foreach ($mail['lines'] ?? [($this->payload['message'] ?? '')] as $line) {
            $message->line($line);
        }

        $actionText = $mail['action_text'] ?? ($this->payload['action_label'] ?? null);
        $actionUrl = $mail['action_url'] ?? ($this->payload['action_url'] ?? null);

        if ($actionText && $actionUrl) {
            $message->action($actionText, $actionUrl);
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
