<?php

namespace App\Services\Notifications\Channels;

use App\Models\User;

interface NotificationChannelInterface
{
    public function code(): string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $recipient, array $payload, ?string $triggerCode = null, ?string $notificationId = null): bool;
}
