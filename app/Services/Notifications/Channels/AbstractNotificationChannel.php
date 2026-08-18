<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDeliveryLog;

abstract class AbstractNotificationChannel implements NotificationChannelInterface
{
    protected function logDelivery(
        ?string $notificationId,
        int $userId,
        ?string $triggerCode,
        string $status,
        \DateTimeInterface $attemptedAt,
        ?\DateTimeInterface $deliveredAt = null,
        ?string $failureReason = null,
        ?array $providerResponse = null,
    ): void {
        NotificationDeliveryLog::query()->create([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'trigger_code' => $triggerCode,
            'channel' => $this->code(),
            'status' => $status,
            'failure_reason' => $failureReason,
            'provider_response' => $providerResponse,
            'attempted_at' => $attemptedAt,
            'delivered_at' => $deliveredAt,
        ]);
    }
}
