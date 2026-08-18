<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\NotificationOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $triggerCode,
        public array $context,
    ) {}

    public function handle(NotificationOrchestrator $orchestrator): void
    {
        $orchestrator->deliver(array_merge($this->context, [
            'trigger_code' => $this->triggerCode,
            'queue' => false,
        ]));
    }
}
