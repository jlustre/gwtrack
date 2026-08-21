<?php

namespace App\Jobs;

use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplySubscriptionAnniversaries implements ShouldQueue
{
    use Queueable;

    public function handle(SubscriptionService $subscriptions): void
    {
        $subscriptions->applyDueAnniversariesForAll();
    }
}
