<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\NotificationChannelInterface;
use App\Services\Notifications\Channels\PushChannel;
use App\Services\Notifications\Channels\SmsChannel;
use InvalidArgumentException;

class NotificationChannelDispatcher
{
    public function __construct(
        private readonly EmailChannel $emailChannel,
        private readonly SmsChannel $smsChannel,
        private readonly PushChannel $pushChannel,
        private readonly NotificationPreferenceService $preferences,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(
        string $channelCode,
        User $recipient,
        array $payload,
        string $typeCode,
        string $priority,
        ?string $triggerCode = null,
        ?string $notificationId = null,
    ): bool {
        if (! $this->shouldDeliver($recipient, $typeCode, $channelCode, $priority)) {
            $this->logSuppressed($channelCode, $recipient, $triggerCode, $notificationId);

            return false;
        }

        return $this->channel($channelCode)->send($recipient, $payload, $triggerCode, $notificationId);
    }

    private function logSuppressed(
        string $channelCode,
        User $recipient,
        ?string $triggerCode,
        ?string $notificationId,
    ): void {
        \App\Models\NotificationDeliveryLog::query()->create([
            'notification_id' => $notificationId,
            'user_id' => $recipient->id,
            'trigger_code' => $triggerCode,
            'channel' => $channelCode,
            'status' => 'suppressed',
            'failure_reason' => 'User preference or channel policy',
            'attempted_at' => now(),
        ]);
    }

    public function shouldDeliver(User $user, string $typeCode, string $channelCode, string $priority): bool
    {
        if ($channelCode === 'in_app') {
            return true;
        }

        $criticalChannels = config('notifications.critical_channels', ['in_app', 'email']);

        if (in_array($priority, config('notifications.critical_priorities', []), true)
            && in_array($channelCode, $criticalChannels, true)) {
            return true;
        }

        return $this->preferences->isChannelEnabled($user, $typeCode, $channelCode);
    }

    private function channel(string $code): NotificationChannelInterface
    {
        return match ($code) {
            'email' => $this->emailChannel,
            'sms' => $this->smsChannel,
            'push' => $this->pushChannel,
            default => throw new InvalidArgumentException("Unsupported notification channel [{$code}]."),
        };
    }
}
