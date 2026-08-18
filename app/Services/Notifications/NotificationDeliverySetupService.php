<?php

namespace App\Services\Notifications;

use App\Models\NotificationDeviceToken;
use App\Models\User;

class NotificationDeliverySetupService
{
    public function __construct(
        private readonly Transports\SmsTransport $sms,
        private readonly Transports\WebPushTransport $webPush,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statusFor(User $user): array
    {
        $phone = trim((string) ($user->profile?->phone ?? ''));
        $activeDevices = NotificationDeviceToken::activeForUser($user->id);

        return [
            'sms' => [
                'enabled_globally' => (bool) config('notifications.sms.enabled', false),
                'configured' => $this->sms->isConfigured(),
                'has_phone' => $phone !== '',
                'phone_masked' => $phone !== '' ? $this->maskPhone($phone) : null,
                'driver' => config('notifications.sms.driver', 'log'),
            ],
            'push' => [
                'enabled_globally' => (bool) config('notifications.push.enabled', false),
                'configured' => $this->webPush->isConfigured(),
                'device_count' => $activeDevices->count(),
                'devices' => $activeDevices->map(fn (NotificationDeviceToken $token): array => [
                    'id' => $token->id,
                    'platform' => $token->platform,
                    'device_name' => $token->device_name ?? ucfirst($token->platform),
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                ])->values()->all(),
                'vapid_public_key' => $this->webPush->publicKey(),
                'driver' => config('notifications.push.driver', 'log'),
                'browser_supported' => true,
            ],
        ];
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;

        if (strlen($digits) <= 4) {
            return $phone;
        }

        return str_repeat('•', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
