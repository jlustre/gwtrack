<?php

namespace App\Services\Notifications;

use App\Models\NotificationDeviceToken;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationDeviceTokenService
{
    public function register(
        User $user,
        string $token,
        string $platform,
        ?string $deviceName = null,
        ?string $subscriptionPayload = null,
    ): NotificationDeviceToken {
        $normalizedToken = $this->normalizeToken($token, $platform, $subscriptionPayload);

        return NotificationDeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $normalizedToken,
            ],
            [
                'platform' => $platform,
                'device_name' => $deviceName,
                'subscription_payload' => $subscriptionPayload,
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );
    }

    public function revoke(User $user, string $token): bool
    {
        return (bool) NotificationDeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    private function normalizeToken(string $token, string $platform, ?string $subscriptionPayload): string
    {
        if ($platform === 'web' && filled($subscriptionPayload)) {
            $decoded = json_decode($subscriptionPayload, true);

            if (is_array($decoded) && filled($decoded['endpoint'] ?? null)) {
                return hash('sha256', (string) $decoded['endpoint']);
            }
        }

        return Str::limit($token, 512, '');
    }
}
