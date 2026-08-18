<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use Illuminate\Support\Str;

class NotificationInsightService
{
    public function enrich(Notification $notification): void
    {
        if (! config('notifications.ai.insights_enabled', false)) {
            return;
        }

        $metadata = $notification->metadata ?? [];

        if (! empty($metadata['ai_summary'])) {
            return;
        }

        $metadata['ai_summary'] = $this->buildSummary($notification);
        $metadata['suggested_actions'] = $this->buildSuggestedActions($notification);
        $metadata['ai_generated_at'] = now()->toIso8601String();

        $notification->forceFill(['metadata' => $metadata])->save();
    }

    public function generateDailySummaries(): int
    {
        if (! config('notifications.ai.insights_enabled', false)) {
            return 0;
        }

        $count = 0;

        Notification::query()
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('id')
            ->limit((int) config('notifications.ai.batch_size', 100))
            ->get()
            ->filter(fn (Notification $notification) => blank(data_get($notification->metadata, 'ai_summary')))
            ->each(function (Notification $notification) use (&$count): void {
                $this->enrich($notification);
                $count++;
            });

        return $count;
    }

    private function buildSummary(Notification $notification): string
    {
        $title = data_get($notification->data, 'title', 'Notification');
        $message = data_get($notification->data, 'message', '');

        return Str::limit(trim("{$title}: {$message}"), 240);
    }

    /**
     * @return list<string>
     */
    private function buildSuggestedActions(Notification $notification): array
    {
        $trigger = data_get($notification->data, 'trigger');

        return match ($trigger) {
            'task_assigned', 'task_overdue' => ['Open task', 'Mark complete'],
            'training_assigned' => ['Open course', 'View training dashboard'],
            'goal_reminder', 'goal_off_track' => ['Review goals', 'Update progress'],
            default => ['Open related record', 'Dismiss notification'],
        };
    }
}
