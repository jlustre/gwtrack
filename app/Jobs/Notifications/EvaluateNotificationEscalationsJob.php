<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\NotificationEscalationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateNotificationEscalationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationEscalationService $escalations): void
    {
        $escalations->evaluateAll();
    }
}
