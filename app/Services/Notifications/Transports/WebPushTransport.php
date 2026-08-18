<?php

namespace App\Services\Notifications\Transports;

use App\Models\NotificationDeviceToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushTransport
{
    /**
     * @param  Collection<int, NotificationDeviceToken>  $tokens
     * @return array{success: bool, message: string, driver: string, sent: int, failed: int}
     */
    public function send(Collection $tokens, string $title, string $body, array $data = []): array
    {
        $driver = (string) config('notifications.push.driver', 'log');

        return match ($driver) {
            'webpush' => $this->sendViaWebPush($tokens, $title, $body, $data),
            default => $this->sendViaLog($tokens, $title, $body, $data),
        };
    }

    public function isConfigured(): bool
    {
        if (! config('notifications.push.enabled', false)) {
            return false;
        }

        return match (config('notifications.push.driver', 'log')) {
            'webpush' => filled(config('notifications.push.vapid.public_key'))
                && filled(config('notifications.push.vapid.private_key')),
            default => true,
        };
    }

    public function publicKey(): ?string
    {
        $key = config('notifications.push.vapid.public_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * @param  Collection<int, NotificationDeviceToken>  $tokens
     * @return array{success: bool, message: string, driver: string, sent: int, failed: int}
     */
    private function sendViaLog(Collection $tokens, string $title, string $body, array $data): array
    {
        Log::info('Notification push (log driver)', [
            'token_count' => $tokens->count(),
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return [
            'success' => true,
            'message' => 'Push notifications logged for delivery.',
            'driver' => 'log',
            'sent' => $tokens->count(),
            'failed' => 0,
        ];
    }

    /**
     * @param  Collection<int, NotificationDeviceToken>  $tokens
     * @return array{success: bool, message: string, driver: string, sent: int, failed: int}
     */
    private function sendViaWebPush(Collection $tokens, string $title, string $body, array $data): array
    {
        $publicKey = (string) config('notifications.push.vapid.public_key');
        $privateKey = (string) config('notifications.push.vapid.private_key');
        $subject = (string) config('notifications.push.vapid.subject', config('app.url'));

        if ($publicKey === '' || $privateKey === '') {
            return [
                'success' => false,
                'message' => 'VAPID keys are not configured.',
                'driver' => 'webpush',
                'sent' => 0,
                'failed' => $tokens->count(),
            ];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ], JSON_THROW_ON_ERROR);

        $queued = 0;

        foreach ($tokens as $deviceToken) {
            $subscription = $this->subscriptionForToken($deviceToken);

            if ($subscription === null) {
                continue;
            }

            $webPush->queueNotification($subscription, $payload);
            $queued++;
        }

        if ($queued === 0) {
            return [
                'success' => false,
                'message' => 'No valid web push subscriptions found.',
                'driver' => 'webpush',
                'sent' => 0,
                'failed' => $tokens->count(),
            ];
        }

        $sent = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            $failed++;
            Log::warning('Web push delivery failed', [
                'reason' => $report->getReason(),
                'endpoint' => $report->getEndpoint(),
            ]);
        }

        return [
            'success' => $sent > 0,
            'message' => $sent > 0 ? 'Push notifications delivered.' : 'All push deliveries failed.',
            'driver' => 'webpush',
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function subscriptionForToken(NotificationDeviceToken $deviceToken): ?Subscription
    {
        $payload = $deviceToken->subscription_payload;

        if (filled($payload)) {
            $decoded = json_decode($payload, true);

            if (is_array($decoded) && filled($decoded['endpoint'] ?? null)) {
                return Subscription::create($decoded);
            }
        }

        if ($deviceToken->platform !== 'web') {
            return null;
        }

        return null;
    }
}
