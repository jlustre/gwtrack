<?php

namespace App\Services\Training;

use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\TrainingSession;
use App\Models\TrainingSessionAttendance;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TrainingCalendarService
{
    public function syncSessionToCalendar(TrainingSession $session): CalendarEvent
    {
        abort_unless($session->instructor_id, 422);

        $type = $this->calendarEventTypeFor($session->session_type ?? 'live');
        $endsAt = $session->ends_at ?? $session->starts_at?->copy()->addHour();

        $payload = [
            'calendar_event_type_id' => $type->id,
            'calendar_category_id' => $type->calendar_category_id,
            'organizer_id' => $session->instructor_id,
            'title' => $session->title,
            'description' => $session->description,
            'starts_at' => $session->starts_at,
            'ends_at' => $endsAt,
            'timezone' => $session->instructor?->profile?->timezone ?? 'America/Vancouver',
            'visibility' => config('training-academy.calendar.visibility', 'public_organization'),
            'status' => 'scheduled',
            'color' => $type->color ?? '#84BB41',
            'related_training_module_id' => $session->training_module_id,
        ];

        $event = $session->calendar_event_id
            ? CalendarEvent::query()->find($session->calendar_event_id)
            : null;

        if ($event) {
            $event->update($payload);
        } else {
            $event = CalendarEvent::query()->create($payload);
            $session->update(['calendar_event_id' => $event->id]);
        }

        return $event->fresh();
    }

    public function syncAttendanceToCalendar(TrainingSessionAttendance $attendance): void
    {
        $session = $attendance->session()->with('instructor.profile')->first();

        if (! $session) {
            return;
        }

        if (! $session->calendar_event_id) {
            $this->syncSessionToCalendar($session);
            $session->refresh();
        }

        CalendarEventAttendee::query()->firstOrCreate(
            [
                'calendar_event_id' => $session->calendar_event_id,
                'user_id' => $attendance->user_id,
            ],
            [
                'attendee_type' => 'user',
                'rsvp_status' => 'accepted',
                'responded_at' => now(),
            ],
        );
    }

    public function checkIn(TrainingSessionAttendance $attendance, User $actor): TrainingSessionAttendance
    {
        $session = $attendance->session;

        abort_unless($session && $this->canManageAttendance($actor, $session), 403);

        $attendance->update([
            'status' => 'attended',
            'checked_in_at' => now(),
        ]);

        if ($session->calendar_event_id) {
            CalendarEventAttendee::query()
                ->where('calendar_event_id', $session->calendar_event_id)
                ->where('user_id', $attendance->user_id)
                ->update([
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]);
        }

        app(TrainingGamificationService::class)->recordSessionAttended($attendance->user);

        return $attendance->fresh();
    }

    public function selfCheckIn(User $user, TrainingSession $session): TrainingSessionAttendance
    {
        $attendance = TrainingSessionAttendance::query()
            ->where('training_session_id', $session->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($session->starts_at && now()->lt($session->starts_at->copy()->subMinutes(30))) {
            throw ValidationException::withMessages([
                'checkin' => 'Check-in opens 30 minutes before the session starts.',
            ]);
        }

        $attendance->update([
            'status' => 'attended',
            'checked_in_at' => now(),
        ]);

        if ($session->calendar_event_id) {
            CalendarEventAttendee::query()
                ->where('calendar_event_id', $session->calendar_event_id)
                ->where('user_id', $user->id)
                ->update([
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]);
        }

        app(TrainingGamificationService::class)->recordSessionAttended($user);

        return $attendance->fresh();
    }

    public function canManageAttendance(User $actor, TrainingSession $session): bool
    {
        if ($actor->can('manage training')) {
            return true;
        }

        return (int) $session->instructor_id === (int) $actor->id;
    }

    /**
     * @return list<array{
     *     session: TrainingSession,
     *     registered: bool,
     *     attended: bool,
     *     seats_remaining: int|null,
     *     calendar_url: string|null
     * }>
     */
    public function upcomingSessionRowsFor(User $user): array
    {
        return TrainingSession::query()
            ->upcoming()
            ->with(['instructor', 'module', 'calendarEvent'])
            ->withCount('attendance')
            ->get()
            ->map(function (TrainingSession $session) use ($user): array {
                $attendance = TrainingSessionAttendance::query()
                    ->where('training_session_id', $session->id)
                    ->where('user_id', $user->id)
                    ->first();

                $seatsRemaining = $session->capacity
                    ? max(0, $session->capacity - $session->attendance_count)
                    : null;

                return [
                    'session' => $session,
                    'registered' => $attendance !== null,
                    'attended' => $attendance?->status === 'attended',
                    'seats_remaining' => $seatsRemaining,
                    'calendar_url' => $session->calendar_event_id
                        ? route('calendar.events.show', $session->calendar_event_id)
                        : null,
                ];
            })
            ->all();
    }

    private function calendarEventTypeFor(string $sessionType): CalendarEventType
    {
        $slug = match ($sessionType) {
            'field' => config('training-academy.calendar.field_type_slug', 'field-observation'),
            'webinar' => config('training-academy.calendar.webinar_type_slug', 'recorded-webinar-review'),
            default => config('training-academy.calendar.event_type_slug', 'training-session'),
        };

        $type = CalendarEventType::query()->where('slug', $slug)->where('is_active', true)->first();

        if ($type) {
            return $type;
        }

        return CalendarEventType::query()->where('slug', 'training-session')->firstOrFail();
    }
}
