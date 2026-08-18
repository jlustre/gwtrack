<?php

namespace App\Services\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\Transports\SmsTransport;
use Illuminate\Support\Str;

class SmsChannel extends AbstractNotificationChannel
{
    public function __construct(
        private readonly SmsTransport $sms,
    ) {}

    public function code(): string
    {
        return 'sms';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $recipient, array $payload, ?string $triggerCode = null, ?string $notificationId = null): bool
    {
        $attemptedAt = now();

        if (! config('notifications.sms.enabled', false)) {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'skipped',
                $attemptedAt,
                null,
                'SMS channel disabled',
            );

            return false;
        }

        $body = trim((string) ($payload['body'] ?? $payload['sms_body'] ?? ''));

        if ($body === '') {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'failed',
                $attemptedAt,
                null,
                'SMS body missing',
            );

            return false;
        }

        $phone = $recipient->profile?->phone;

        if (! $phone) {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'failed',
                $attemptedAt,
                null,
                'Recipient has no phone number',
            );

            return false;
        }

        $body = Str::limit($body, (int) config('notifications.sms.max_length', 160), '');

        $result = $this->sms->send($phone, $body);

        $this->logDelivery(
            $notificationId,
            $recipient->id,
            $triggerCode,
            $result['success'] ? 'sent' : 'failed',
            $attemptedAt,
            $result['success'] ? now() : null,
            $result['success'] ? null : $result['message'],
            [
                'driver' => $result['driver'],
                'sid' => $result['sid'] ?? null,
            ],
        );

        return $result['success'];
    }
}
