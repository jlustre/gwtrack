<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDeviceToken;
use App\Models\User;
use App\Services\Notifications\Transports\WebPushTransport;

class PushChannel extends AbstractNotificationChannel
{
    public function __construct(
        private readonly WebPushTransport $webPush,
    ) {}

    public function code(): string
    {
        return 'push';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $recipient, array $payload, ?string $triggerCode = null, ?string $notificationId = null): bool
    {
        $attemptedAt = now();

        if (! config('notifications.push.enabled', false)) {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'skipped',
                $attemptedAt,
                null,
                'Push channel disabled',
            );

            return false;
        }

        $tokens = NotificationDeviceToken::activeForUser($recipient->id);

        if ($tokens->isEmpty()) {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'skipped',
                $attemptedAt,
                null,
                'No active device tokens',
            );

            return false;
        }

        $title = trim((string) ($payload['title'] ?? config('app.name')));
        $body = trim((string) ($payload['body'] ?? ''));

        if ($body === '') {
            $this->logDelivery(
                $notificationId,
                $recipient->id,
                $triggerCode,
                'failed',
                $attemptedAt,
                null,
                'Push body missing',
            );

            return false;
        }

        $result = $this->webPush->send($tokens, $title, $body, [
            'url' => $payload['action_url'] ?? $payload['url'] ?? null,
            'notification_id' => $notificationId,
            'trigger' => $triggerCode,
        ]);

        if ($result['success']) {
            foreach ($tokens as $deviceToken) {
                $deviceToken->forceFill(['last_used_at' => now()])->save();
            }
        }

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
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ],
        );

        return $result['success'];
    }
}
