<?php

namespace App\Services\Notifications;

use App\Jobs\Notifications\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationEscalationLog;
use App\Models\NotificationTrigger;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationAdminService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardMetrics(): array
    {
        $since24h = now()->subDay();
        $since7d = now()->subDays(7);

        return [
            'sent_24h' => NotificationDeliveryLog::query()->where('attempted_at', '>=', $since24h)->count(),
            'sent_7d' => NotificationDeliveryLog::query()->where('attempted_at', '>=', $since7d)->count(),
            'failed_24h' => NotificationDeliveryLog::query()
                ->where('attempted_at', '>=', $since24h)
                ->where('status', 'failed')
                ->count(),
            'unread_total' => DB::table('notifications')
                ->whereNull('deleted_at')
                ->whereNull('read_at')
                ->whereNull('archived_at')
                ->count(),
            'critical_open' => DB::table('notifications')
                ->whereNull('deleted_at')
                ->whereNull('read_at')
                ->where('priority', 'critical')
                ->count(),
            'active_escalations' => NotificationEscalationLog::query()
                ->where('created_at', '>=', $since7d)
                ->count(),
            'inactive_triggers' => DB::table('notification_triggers')
                ->whereNull('deleted_at')
                ->where('is_active', false)
                ->count(),
            'top_triggers' => $this->topTriggerCodes($since7d),
            'channel_breakdown' => $this->channelBreakdown($since7d),
        ];
    }

    public function deliveryLogs(
        ?string $status = null,
        ?string $channel = null,
        ?string $search = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return NotificationDeliveryLog::query()
            ->with(['user:id,name,email'])
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->when(filled($channel), fn ($query) => $query->where('channel', $channel))
            ->when(filled($search), function ($query) use ($search) {
                $needle = '%'.$search.'%';
                $query->where(function ($inner) use ($needle) {
                    $inner->where('trigger_code', 'like', $needle)
                        ->orWhere('failure_reason', 'like', $needle)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', $needle)
                            ->orWhere('email', 'like', $needle));
                });
            })
            ->orderByDesc('attempted_at')
            ->paginate($perPage);
    }

    public function resendDeliveryLog(int $logId): bool
    {
        $log = NotificationDeliveryLog::query()->findOrFail($logId);

        if ($log->status !== 'failed' || blank($log->trigger_code)) {
            return false;
        }

        $notification = $log->notification_id
            ? Notification::query()->find($log->notification_id)
            : null;

        $recipientId = $log->user_id ?? $notification?->notifiable_id;

        if (! $recipientId) {
            return false;
        }

        $payload = json_decode((string) $notification?->data, true) ?: [];

        SendNotificationJob::dispatch($log->trigger_code, [
            'recipients' => ['user_ids' => [$recipientId]],
            'template_data' => $payload,
            'priority' => $notification?->priority ?? 'medium',
            'action_link' => json_decode((string) $notification?->action_link, true) ?: [],
        ]);

        NotificationDeliveryLog::query()->create([
            'notification_id' => $log->notification_id,
            'user_id' => $recipientId,
            'trigger_code' => $log->trigger_code,
            'channel' => $log->channel,
            'status' => 'queued',
            'attempted_at' => now(),
        ]);

        return true;
    }

    public function sendTemplateTest(int $triggerId, User $admin): bool
    {
        $trigger = NotificationTrigger::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->find($triggerId);

        if (! $trigger) {
            return false;
        }

        SendNotificationJob::dispatch($trigger->code, [
            'recipients' => ['user_ids' => [$admin->id]],
            'template_data' => [
                'user_name' => $admin->name,
                'member_name' => $admin->name,
                'app_name' => config('app.name'),
            ],
            'priority' => 'low',
            'action_link' => ['url' => route('notifications.index')],
        ]);

        return true;
    }

    /**
     * @return Collection<int, object>
     */
    private function topTriggerCodes(\DateTimeInterface $since): Collection
    {
        return NotificationDeliveryLog::query()
            ->select('trigger_code', DB::raw('COUNT(*) as total'))
            ->where('attempted_at', '>=', $since)
            ->whereNotNull('trigger_code')
            ->groupBy('trigger_code')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function channelBreakdown(\DateTimeInterface $since): Collection
    {
        return NotificationDeliveryLog::query()
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->where('attempted_at', '>=', $since)
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();
    }
}
