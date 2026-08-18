<?php

namespace App\Services\Notifications\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailChannel extends AbstractNotificationChannel
{
    public function code(): string
    {
        return 'email';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $recipient, array $payload, ?string $triggerCode = null, ?string $notificationId = null): bool
    {
        $attemptedAt = now();
        $mail = $payload['mail'] ?? $payload;

        try {
            Mail::send(
                'emails.notifications.plain',
                ['mail' => $mail],
                function ($message) use ($recipient, $mail): void {
                    $message->to($recipient->email)
                        ->subject($mail['subject'] ?? config('app.name').' notification');
                },
            );

            $this->logDelivery($notificationId, $recipient->id, $triggerCode, 'sent', $attemptedAt, now());

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification email delivery failed', [
                'user_id' => $recipient->id,
                'trigger' => $triggerCode,
                'error' => $exception->getMessage(),
            ]);

            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'failed',
                $attemptedAt,
                null,
                $exception->getMessage(),
            );

            return false;
        }
    }
}
