<?php

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\Notification;
use App\Models\NotificationDigestSetting;
use App\Models\ProspectFollowUp;
use App\Models\User;
use App\Models\TaskUser;
use App\Services\Notifications\Channels\EmailChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationDigestService
{
    public function __construct(
        private readonly EmailChannel $emailChannel,
    ) {}

    public function sendDueDigests(string $digestType = 'daily'): int
    {
        $sent = 0;

        User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->chunkById(100, function ($users) use ($digestType, &$sent): void {
                foreach ($users as $user) {
                    if ($this->sendDigestIfDue($user, $digestType)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendDigestIfDue(User $user, string $digestType): bool
    {
        if (! $this->isDue($user, $digestType)) {
            return false;
        }

        $sections = $this->buildSections($user, $digestType);

        if ($this->sectionsEmpty($sections)) {
            $this->touchLastSent($user, $digestType);

            return false;
        }

        $subject = $digestType === 'weekly'
            ? config('app.name').' weekly notification digest'
            : config('app.name').' daily notification digest';

        $lines = $this->flattenSections($sections);

        $this->emailChannel->send($user, [
            'subject' => $subject,
            'greeting' => "Hello {$user->name},",
            'lines' => $lines,
            'action_text' => 'Open notification center',
            'action_url' => route('notifications.index', [], absolute: true),
        ], "{$digestType}_digest");

        $this->touchLastSent($user, $digestType);

        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function buildSections(User $user, string $digestType): array
    {
        $since = $digestType === 'weekly' ? now()->subWeek() : now()->subDay();

        $sections = [
            'Unread notifications' => $this->unreadNotificationLines($user, $since),
            'Tasks due or overdue' => $this->taskLines($user),
            'Prospect follow-ups' => $this->prospectFollowUpLines($user),
            'Upcoming meetings' => $this->upcomingEventLines($user),
        ];

        return array_filter($sections, fn (array $lines) => $lines !== []);
    }

    private function isDue(User $user, string $digestType): bool
    {
        if (! config("notifications.digest.{$digestType}.enabled", true)) {
            return false;
        }

        $setting = $this->resolveSetting($user, $digestType);

        if (! $setting['enabled']) {
            return false;
        }

        $timezone = $this->resolveTimezone($user, $setting['timezone_id']);
        $now = Carbon::now($timezone);

        if ($digestType === 'weekly') {
            $sendDay = $setting['send_day'] ?? config('notifications.digest.weekly.default_day', 1);

            if ((int) $now->dayOfWeek !== (int) $sendDay) {
                return false;
            }
        }

        [$hour, $minute] = array_pad(explode(':', $setting['send_at']), 2, '00');
        $scheduled = $now->copy()->setTime((int) $hour, (int) $minute, 0);

        if ($now->lt($scheduled)) {
            return false;
        }

        $lastSent = $setting['last_sent_at'];

        if ($lastSent && $lastSent->copy()->timezone($timezone)->isSameDay($now)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{enabled: bool, send_at: string, send_day: int|null, timezone_id: int|null, last_sent_at: ?Carbon}
     */
    private function resolveSetting(User $user, string $digestType): array
    {
        $saved = NotificationDigestSetting::query()
            ->where('user_id', $user->id)
            ->where('digest_type', $digestType)
            ->first();

        if ($saved) {
            return [
                'enabled' => $saved->enabled,
                'send_at' => $saved->send_at ?? config("notifications.digest.{$digestType}.default_time", '07:00'),
                'send_day' => $saved->send_day,
                'timezone_id' => $saved->timezone_id,
                'last_sent_at' => $saved->last_sent_at,
            ];
        }

        return [
            'enabled' => true,
            'send_at' => config("notifications.digest.{$digestType}.default_time", '07:00'),
            'send_day' => config('notifications.digest.weekly.default_day', 1),
            'timezone_id' => $user->profile?->timezone_id,
            'last_sent_at' => null,
        ];
    }

    private function resolveTimezone(User $user, ?int $timezoneId): string
    {
        $timezoneId ??= $user->profile?->timezone_id;

        if ($timezoneId) {
            $code = \App\Models\Timezone::query()->whereKey($timezoneId)->value('code');

            if ($code) {
                return $code;
            }
        }

        return config('app.timezone', 'UTC');
    }

    private function touchLastSent(User $user, string $digestType): void
    {
        NotificationDigestSetting::query()->updateOrCreate(
            ['user_id' => $user->id, 'digest_type' => $digestType],
            ['last_sent_at' => now(), 'enabled' => true],
        );
    }

    /**
     * @return list<string>
     */
    private function unreadNotificationLines(User $user, Carbon $since): array
    {
        return Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Notification $notification) => '• '.($notification->data['title'] ?? 'Notification'))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function taskLines(User $user): array
    {
        return TaskUser::query()
            ->where('assignee_id', $user->id)
            ->whereNull('completed_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query): void {
                $query->whereDate('due_date', '<=', now()->addDay())
                    ->orWhereDate('due_date', '<', now());
            })
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function (TaskUser $task): string {
                $due = $task->due_date?->format('M j') ?? 'No date';

                return "• {$task->displayTitle()} (due {$due})";
            })
            ->all();
    }

    /**
     * @return list<string>
     */
    private function prospectFollowUpLines(User $user): array
    {
        return ProspectFollowUp::query()
            ->with('prospect')
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_at', '<=', now()->addDays(2))
            ->orderBy('due_at')
            ->limit(10)
            ->get()
            ->map(fn (ProspectFollowUp $followUp) => '• '.($followUp->prospect?->displayName() ?? 'Prospect').' — due '.$followUp->due_at?->format('M j'))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function upcomingEventLines(User $user): array
    {
        return CalendarEvent::query()
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(48))
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($q) => $q->where('user_id', $user->id));
            })
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (CalendarEvent $event) => '• '.$event->title.' — '.$event->starts_at?->format('M j, g:i A'))
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function sectionsEmpty(array $sections): bool
    {
        return collect($sections)->flatten()->isEmpty();
    }

    /**
     * @param  array<string, list<string>>  $sections
     * @return list<string>
     */
    private function flattenSections(array $sections): array
    {
        $lines = [];

        foreach ($sections as $heading => $items) {
            $lines[] = $heading.':';

            foreach ($items as $item) {
                $lines[] = $item;
            }

            $lines[] = '';
        }

        return $lines;
    }
}
