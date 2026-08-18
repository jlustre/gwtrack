<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\NotificationDigestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationDigestsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $digestType = 'daily',
    ) {}

    public function handle(NotificationDigestService $digests): void
    {
        $digests->sendDueDigests($this->digestType);
    }
}
