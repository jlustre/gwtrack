<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ComplianceLifecycleService;
use App\Services\Notifications\NotificationOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchComplianceRenewalReminders implements ShouldQueue
{
    use Queueable;

    public function handle(
        ComplianceLifecycleService $compliance,
        NotificationOrchestrator $notifications,
    ): void {
        $trigger = config('compliance-lifecycle.notification_trigger', 'compliance_renewal_due');

        foreach ($compliance->recordsNeedingReminder() as $record) {
            $user = User::query()->find($record->user_id);

            if ($user === null) {
                continue;
            }

            $notifications->dispatch($trigger, [
                'user_id' => $user->id,
                'recipient' => $user,
                'title' => 'Compliance renewal due',
                'body' => "{$record->title} expires on {$record->expiration_date?->format('M j, Y')}.",
                'action_url' => route('compliance.index'),
                'meta' => [
                    'compliance_record_id' => $record->id,
                    'compliance_type' => $record->compliance_type,
                    'days_until_expiration' => $record->daysUntilExpiration(),
                ],
            ]);

            $record->update(['last_reminder_at' => now()]);
        }
    }
}
