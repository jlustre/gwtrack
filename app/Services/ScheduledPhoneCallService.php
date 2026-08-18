<?php

namespace App\Services;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Models\Prospect;
use App\Models\ScheduledPhoneCall;
use App\Models\User;
use App\Services\Prospects\ProspectFunnelService;
use App\Services\Prospects\ProspectTaskBridge;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduledPhoneCallService
{
    public function __construct(
        private readonly ProspectFunnelService $funnels,
        private readonly ProspectTaskBridge $taskBridge,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function schedule(User $user, array $payload): ScheduledPhoneCall
    {
        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => $payload['person_type'],
            'prospect_id' => $payload['prospect_id'] ?? null,
            'related_user_id' => $payload['related_user_id'] ?? null,
            'contact_name' => $payload['contact_name'],
            'contact_phone' => $payload['contact_phone'] ?? null,
            'contact_email' => $payload['contact_email'] ?? null,
            'scheduled_at' => $payload['scheduled_at'],
            'timezone' => $payload['timezone'] ?? config('app.timezone', 'America/Vancouver'),
            'purpose' => $payload['purpose'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'status' => 'scheduled',
            'rescheduled_from_id' => $payload['rescheduled_from_id'] ?? null,
        ]);

        if ($payload['sync_calendar'] ?? config('scheduled-phone-calls.sync.calendar', true)) {
            $this->syncCalendar($call);
        }

        if (($payload['create_prep_task'] ?? config('scheduled-phone-calls.sync.prep_task', true)) && $call->prospect_id) {
            $this->createPrepTask($call);
        }

        return $call->fresh(['prospect', 'relatedUser', 'calendarEvent']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordResult(User $user, ScheduledPhoneCall $call, array $payload): ScheduledPhoneCall
    {
        abort_unless($call->user_id === $user->id, 403);
        abort_unless($call->isOpen(), 422, 'This phone call has already been completed.');

        $result = (string) $payload['result'];
        $resultNotes = filled($payload['result_notes'] ?? null) ? (string) $payload['result_notes'] : null;
        $followUpAt = $payload['callback_at'] ?? $payload['reschedule_at'] ?? null;
        $shouldLogCrm = $this->shouldLogCrmActivity($call);

        $activityId = null;

        if ($shouldLogCrm) {
            $prospect = Prospect::query()->findOrFail($call->prospect_id);
            $activity = $this->funnels->logActivity($prospect, $user, [
                'activity_type' => 'phone_call',
                'occurred_at' => now(),
                'outcome' => $this->activityOutcomeForResult($result),
                'notes' => collect([
                    $call->purpose,
                    $resultNotes,
                ])->filter()->implode("\n"),
                'next_action' => match ($result) {
                    'not_contacted' => 'Try calling again',
                    'callback' => 'Call back',
                    default => null,
                },
                'next_follow_up_at' => match ($result) {
                    'callback' => filled($followUpAt)
                        ? ($followUpAt instanceof CarbonInterface ? $followUpAt : Carbon::parse($followUpAt))
                        : null,
                    'not_contacted' => $call->scheduled_at,
                    default => null,
                },
                'metadata' => [
                    'scheduled_phone_call_id' => $call->id,
                    'call_result' => $result,
                ],
            ]);
            $activityId = $activity->id;
        }

        if ($result === 'completed') {
            $call->forceFill([
                'status' => 'completed',
                'result' => $result,
                'result_notes' => $resultNotes,
                'prospect_activity_id' => $activityId,
                'completed_at' => now(),
            ])->save();

            if ($call->calendar_event_id) {
                CalendarEvent::query()
                    ->whereKey($call->calendar_event_id)
                    ->update(['status' => 'completed']);
            }

            return $call->fresh(['prospectActivity']);
        }

        $updates = [
            'result' => $result,
            'result_notes' => $resultNotes,
            'prospect_activity_id' => $activityId,
            'status' => 'scheduled',
            'completed_at' => null,
        ];

        if ($result === 'callback' && filled($followUpAt)) {
            $callbackAt = $followUpAt instanceof CarbonInterface
                ? $followUpAt
                : Carbon::parse($followUpAt);

            $updates['scheduled_at'] = $callbackAt;
            $updates['rescheduled_to'] = $callbackAt;

            if (filled($resultNotes)) {
                $updates['notes'] = $resultNotes;
            }
        }

        $call->forceFill($updates)->save();
        $this->syncCalendar($call->fresh());

        return $call->fresh(['prospectActivity', 'calendarEvent']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, ScheduledPhoneCall $call, array $payload): ScheduledPhoneCall
    {
        abort_unless($call->user_id === $user->id, 403);
        abort_unless($call->isOpen(), 422, 'This phone call can no longer be edited.');

        $call->forceFill([
            'contact_name' => $payload['contact_name'],
            'contact_phone' => $payload['contact_phone'] ?? null,
            'contact_email' => $payload['contact_email'] ?? null,
            'scheduled_at' => $payload['scheduled_at'],
            'purpose' => $payload['purpose'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ])->save();

        if ($payload['sync_calendar'] ?? config('scheduled-phone-calls.sync.calendar', true)) {
            $this->syncCalendar($call);
        }

        return $call->fresh(['calendarEvent']);
    }

    public function reschedule(User $user, ScheduledPhoneCall $call, CarbonInterface $scheduledAt, ?string $notes = null): ScheduledPhoneCall
    {
        abort_unless($call->user_id === $user->id, 403);
        abort_unless($call->isOpen(), 422, 'This phone call can no longer be rescheduled.');

        $call->forceFill([
            'scheduled_at' => $scheduledAt,
            'notes' => filled($notes) ? $notes : $call->notes,
        ])->save();

        $this->syncCalendar($call);

        return $call->fresh(['calendarEvent']);
    }

    public function delete(User $user, ScheduledPhoneCall $call): void
    {
        abort_unless($call->user_id === $user->id, 403);

        if ($call->calendar_event_id) {
            CalendarEvent::query()
                ->whereKey($call->calendar_event_id)
                ->update(['status' => 'cancelled']);
        }

        $call->delete();
    }

    /**
     * @return array{
     *     today: Collection<int, ScheduledPhoneCall>,
     *     tomorrow: Collection<int, ScheduledPhoneCall>,
     *     later: Collection<int, ScheduledPhoneCall>,
     *     follow_up: Collection<int, ScheduledPhoneCall>,
     *     stats: array{total: int, today: int, tomorrow: int, follow_up: int, active: int}
     * }
     */
    public function upcomingPanelsFor(User $user, int $limit = 15): array
    {
        $openCalls = ScheduledPhoneCall::query()
            ->with(['prospect:id,first_name,last_name,preferred_name,phone', 'relatedUser:id,name'])
            ->where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get();

        $now = now();
        $today = $now->toDateString();
        $tomorrow = $now->copy()->addDay()->toDateString();

        $followUpCalls = $openCalls
            ->filter(fn (ScheduledPhoneCall $call): bool => $call->scheduled_at->lt($now))
            ->values();

        $activeCalls = $openCalls
            ->filter(fn (ScheduledPhoneCall $call): bool => $call->scheduled_at->gte($now));

        $todayCalls = $activeCalls
            ->filter(fn (ScheduledPhoneCall $call): bool => $call->scheduled_at->toDateString() === $today)
            ->values();

        $tomorrowCalls = $activeCalls
            ->filter(fn (ScheduledPhoneCall $call): bool => $call->scheduled_at->toDateString() === $tomorrow)
            ->values();

        $laterCalls = $activeCalls
            ->filter(fn (ScheduledPhoneCall $call): bool => $call->scheduled_at->toDateString() > $tomorrow)
            ->values();

        return [
            'today' => $todayCalls->take($limit),
            'tomorrow' => $tomorrowCalls->take($limit),
            'later' => $laterCalls->take($limit),
            'follow_up' => $followUpCalls->take($limit),
            'stats' => [
                'total' => $openCalls->count(),
                'today' => $todayCalls->count(),
                'tomorrow' => $tomorrowCalls->count(),
                'follow_up' => $followUpCalls->count(),
                'active' => $activeCalls->count(),
            ],
        ];
    }

    public function syncCalendar(ScheduledPhoneCall $call): CalendarEvent
    {
        $call->loadMissing(['prospect', 'user']);

        $eventType = CalendarEventType::query()
            ->where('slug', 'crm-follow-up')
            ->where('is_active', true)
            ->first()
            ?? CalendarEventType::query()->where('is_active', true)->orderBy('sort_order')->first();

        $category = CalendarCategory::query()->where('slug', 'prospects')->first();
        $startsAt = $call->scheduled_at;
        $duration = (int) config('scheduled-phone-calls.sync.default_duration_minutes', 30);

        $payload = [
            'calendar_event_type_id' => $eventType?->id,
            'calendar_category_id' => $category?->id ?? $eventType?->calendar_category_id,
            'organizer_id' => $call->user_id,
            'title' => 'Phone call: '.$call->contact_name,
            'description' => collect([
                $call->personTypeLabel(),
                filled($call->contact_phone) ? 'Phone: '.$call->contact_phone : null,
                filled($call->purpose) ? 'Purpose: '.$call->purpose : null,
                $call->notes,
            ])->filter()->implode("\n"),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt?->copy()->addMinutes(max(15, $duration)),
            'timezone' => $call->timezone ?? config('app.timezone', 'America/Vancouver'),
            'location' => $call->contact_phone,
            'visibility' => 'private',
            'status' => 'scheduled',
            'related_prospect_id' => $call->prospect_id,
            'related_contact_id' => $call->prospect?->contact_id,
            'notes' => $call->notes,
        ];

        if ($call->calendar_event_id) {
            $event = CalendarEvent::query()->findOrFail($call->calendar_event_id);
            $event->update($payload);
        } else {
            $event = CalendarEvent::create($payload);
            $call->update(['calendar_event_id' => $event->id]);
        }

        return $event->fresh();
    }

    private function createPrepTask(ScheduledPhoneCall $call): void
    {
        $prospect = $call->prospect;

        if (! $prospect) {
            return;
        }

        $titleTemplate = (string) config('scheduled-phone-calls.sync.prep_task_title', 'Prepare for phone call: :contact');
        $title = str_replace(':contact', $call->contact_name, $titleTemplate);
        $description = collect([
            filled($call->purpose) ? 'Purpose: '.$call->purpose : null,
            filled($call->contact_phone) ? 'Phone: '.$call->contact_phone : null,
            'Scheduled: '.$call->scheduled_at->format('M j, Y g:i A'),
        ])->filter()->implode("\n");

        $offsetDays = (int) config('scheduled-phone-calls.sync.prep_task_offset_days', 0);
        $dueDate = $call->scheduled_at->copy()->subDays(max(0, $offsetDays))->toDateString();

        if ($dueDate < now()->toDateString()) {
            $dueDate = now()->toDateString();
        }

        $this->taskBridge->createFromScheduledAppointment($prospect, $title, $description, $dueDate);
    }

    private function shouldLogCrmActivity(ScheduledPhoneCall $call): bool
    {
        if (! filled($call->prospect_id)) {
            return false;
        }

        return in_array($call->person_type, ['prospect', 'client'], true);
    }

    private function activityOutcomeForResult(string $result): string
    {
        $outcomes = config('scheduled-phone-calls.crm_outcomes', []);

        if (isset($outcomes[$result])) {
            return (string) $outcomes[$result];
        }

        return match ($result) {
            'connected' => 'Connected',
            'completed' => 'Completed',
            'reschedule' => 'Reschedule requested',
            default => ucfirst(str_replace('_', ' ', $result)),
        };
    }
}
