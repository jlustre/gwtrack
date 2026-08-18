<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\NotificationInsightService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateNotificationInsightsJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationInsightService $insights): void
    {
        $insights->generateDailySummaries();
    }
}
