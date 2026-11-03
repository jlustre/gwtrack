<?php

namespace App\Services;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySchedule;
use App\Models\BlackoutDate;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingAnswer;
use App\Models\BookingAttendee;
use App\Models\BookingCancellation;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\BookingReschedule;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventReminder;
use App\Models\CalendarEventType;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingWorkflowService
{
    private const ACTIVE_STATUSES = ['pending_approval', 'confirmed'];

    /**
     * Return bookable start times formatted in the availability schedule's timezone.
     *
     * @return list<array{starts_at: string, ends_at: string, label: string}>
     */
    public function availableSlots(
        BookingEventType $eventType,
        ?BookingLink $link,
        CarbonInterface $date,
        ?User $trainee = null,
        ?Booking $ignore = null,
    ): array
    {
        $schedule = $this->scheduleFor($eventType, $link);
        $localDate = Carbon::parse($date->format('Y-m-d'), $schedule->timezone);

        if (! $schedule->is_active || $this->isBlackout($schedule->user_id, $localDate)) {
            return [];
        }

        $overrides = AvailabilityOverride::query()
            ->where('availability_schedule_id', $schedule->id)
            ->whereDate('override_date', $localDate->toDateString())
            ->orderBy('starts_at')
            ->get();

        if ($overrides->isNotEmpty()) {
            $windows = $overrides
                ->where('is_available', true)
                ->filter(fn (AvailabilityOverride $override): bool => $override->starts_at && $override->ends_at)
                ->map(fn (AvailabilityOverride $override): array => [$override->starts_at->format('H:i'), $override->ends_at->format('H:i')])
                ->all();
        } else {
            $weekday = $localDate->isoWeekday();
            $windows = AvailabilityRule::query()
                ->where('availability_schedule_id', $schedule->id)
                ->where('weekday', $weekday)
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (AvailabilityRule $rule): array => [$rule->starts_at->format('H:i'), $rule->ends_at->format('H:i')])
                ->all();
        }

        $slots = [];
        $duration = (int) $eventType->duration_minutes;
        $step = max(5, $duration + (int) $eventType->buffer_before_minutes + (int) $eventType->buffer_after_minutes);
        $now = now($schedule->timezone);
        $minimumStart = $now->copy()->addMinutes((int) $eventType->minimum_notice_minutes);
        $maximumDate = $now->copy()->addDays((int) $eventType->maximum_booking_days_ahead)->endOfDay();
        $dayBookings = $this->activeBookingQuery($eventType->owner_id, $trainee?->id, $ignore?->id);

        foreach ($windows as [$from, $to]) {
            $windowStart = Carbon::parse($localDate->toDateString().' '.$from, $schedule->timezone);
            $windowEnd = Carbon::parse($localDate->toDateString().' '.$to, $schedule->timezone);

            for ($start = $windowStart->copy(); $start->copy()->addMinutes($duration)->lte($windowEnd); $start->addMinutes($step)) {
                $end = $start->copy()->addMinutes($duration);
                if ($start->lt($minimumStart) || $start->gt($maximumDate)) {
                    continue;
                }

                if ($this->conflicts($eventType, $schedule, $start, $end, $ignore, $trainee)) {
                    continue;
                }

                if ($eventType->daily_booking_limit !== null) {
                    $dailyCount = (clone $dayBookings)
                        ->whereBetween('starts_at', [
                            $start->copy()->startOfDay(),
                            $start->copy()->endOfDay(),
                        ])
                        ->count();
                    if ($dailyCount >= $eventType->daily_booking_limit) {
                        continue;
                    }
                }

                if ($eventType->weekly_booking_limit !== null) {
                    $weeklyCount = (clone $dayBookings)
                        ->whereBetween('starts_at', [
                            $start->copy()->startOfWeek(),
                            $start->copy()->endOfWeek(),
                        ])
                        ->count();
                    if ($weeklyCount >= $eventType->weekly_booking_limit) {
                        continue;
                    }
                }

                $slots[] = [
                    'starts_at' => $start->toIso8601String(),
                    'ends_at' => $end->toIso8601String(),
                    'label' => $start->format('g:i A'),
                ];
            }
        }

        return $slots;
    }

    public function createBooking(
        BookingEventType $eventType,
        ?BookingLink $link,
        CarbonInterface $startsAt,
        ?User $trainee,
        ?string $guestName,
        ?string $guestEmail,
        array $answers,
        ?string $reason = null,
        ?string $topics = null,
    ): Booking {
        $schedule = $this->scheduleFor($eventType, $link);
        $start = Carbon::parse($startsAt);
        $end = $start->copy()->addMinutes($eventType->duration_minutes);

        $this->assertCanBook($eventType, $link, $trainee);
        $this->assertSlotAvailable($eventType, $link, $start, $end, trainee: $trainee);
        $this->validateAnswers($eventType, $answers);

        if (! $trainee && (! $guestName || ! filter_var($guestEmail, FILTER_VALIDATE_EMAIL))) {
            throw ValidationException::withMessages([
                'guestEmail' => 'Enter your name and a valid email address to book.',
            ]);
        }

        return DB::transaction(function () use ($eventType, $link, $schedule, $start, $end, $trainee, $guestName, $guestEmail, $answers, $reason, $topics): Booking {
            AvailabilitySchedule::query()->whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($link) {
                $link = BookingLink::query()->whereKey($link->id)->lockForUpdate()->firstOrFail();
                $this->assertCanBook($eventType, $link, $trainee);
            }
            $this->assertSlotAvailable($eventType, $link, $start, $end, trainee: $trainee);

            $status = $eventType->approval_required ? 'pending_approval' : 'confirmed';
            $booking = Booking::query()->create([
                'booking_event_type_id' => $eventType->id,
                'booking_link_id' => $link?->id,
                'availability_schedule_id' => $schedule->id,
                'cfm_id' => $eventType->owner_id,
                'trainee_id' => $trainee?->id,
                'status' => $status,
                'starts_at' => $start,
                'ends_at' => $end,
                'timezone' => $schedule->timezone,
                'location_type' => $eventType->location_type,
                'location_details' => $eventType->location_details,
                'meeting_link' => $eventType->meeting_link,
                'reason' => $reason,
                'topics' => $topics,
                'confirmed_at' => $status === 'confirmed' ? now() : null,
            ]);

            if ($trainee) {
                BookingAttendee::query()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $trainee->id,
                    'name' => $trainee->name,
                    'email' => $trainee->email,
                    'attendee_type' => 'trainee',
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]);
            } else {
                BookingAttendee::query()->create([
                    'booking_id' => $booking->id,
                    'name' => $guestName,
                    'email' => $guestEmail,
                    'attendee_type' => 'guest',
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]);
            }

            foreach ($answers as $questionId => $answer) {
                if ($answer === null || $answer === '' || $answer === []) {
                    continue;
                }
                BookingAnswer::query()->create([
                    'booking_id' => $booking->id,
                    'booking_question_id' => (int) $questionId,
                    'answer' => $answer,
                ]);
            }

            BookingActivityLog::query()->create([
                'booking_id' => $booking->id,
                'user_id' => $trainee?->id,
                'action' => 'booking_created',
                'payload' => ['status' => $status, 'starts_at' => $start->toIso8601String()],
            ]);

            if ($link) {
                $link->increment('uses_count');
                $link->forceFill(['used_at' => $link->is_one_time ? now() : $link->used_at])->save();
            }

            if ($status === 'confirmed') {
                $this->syncCalendarEvent($booking, $eventType);
                $this->notify('booking_confirmed', [$trainee?->id], $booking, [
                    'mentor_name' => $booking->cfm->name,
                ]);
            } else {
                $this->notify('booking_request_received', [$booking->cfm_id], $booking, [
                    'trainee_name' => $trainee?->name ?? $guestName ?? 'A guest',
                ]);
            }

            return $booking->load(['eventType', 'cfm', 'trainee']);
        });
    }

    public function approve(Booking $booking, User $actor): Booking
    {
        abort_unless($actor->can('approve', $booking), 403);
        abort_unless($booking->status === 'pending_approval', 422, 'Only pending bookings can be approved.');

        return DB::transaction(function () use ($booking, $actor): Booking {
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->assertSlotAvailable($locked->eventType, $locked->link, $locked->starts_at, $locked->ends_at, $locked, $locked->trainee);
            $locked->forceFill(['status' => 'confirmed', 'confirmed_at' => now(), 'declined_at' => null])->save();
            $this->syncCalendarEvent($locked, $locked->eventType);
            $this->log($locked, $actor, 'booking_approved');
            $this->notify('booking_confirmed', [$locked->trainee_id], $locked, [
                'mentor_name' => $actor->name,
            ]);

            return $locked->fresh(['eventType', 'cfm', 'trainee']);
        });
    }

    public function decline(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        abort_unless($actor->can('decline', $booking), 403);
        abort_unless($booking->status === 'pending_approval', 422, 'Only pending bookings can be declined.');

        $booking->forceFill(['status' => 'declined', 'declined_at' => now()])->save();
        $this->log($booking, $actor, 'booking_declined', ['reason' => $reason]);
        $this->notify('booking_declined', [$booking->trainee_id], $booking, [
            'mentor_name' => $actor->name,
        ]);

        return $booking->fresh();
    }

    public function requestReschedule(Booking $booking, User $actor, CarbonInterface $newStart, ?string $reason = null): BookingReschedule
    {
        abort_unless($actor->can('reschedule', $booking), 403);
        abort_unless(in_array($booking->status, ['pending_approval', 'confirmed'], true), 422, 'This booking cannot be rescheduled.');
        abort_unless($booking->starts_at->isFuture(), 422, 'Past bookings cannot be rescheduled.');
        abort_if($booking->reschedules()->where('status', 'pending')->exists(), 422, 'A reschedule request is already awaiting review.');

        $newStart = Carbon::parse($newStart);
        abort_if($newStart->equalTo($booking->starts_at), 422, 'Choose a different time for the reschedule request.');
        $newEnd = $newStart->copy()->addMinutes($booking->eventType->duration_minutes);
        $this->assertSlotAvailable($booking->eventType, $booking->link, $newStart, $newEnd, $booking, $booking->trainee);

        $reschedule = BookingReschedule::query()->create([
            'booking_id' => $booking->id,
            'requested_by' => $actor->id,
            'old_starts_at' => $booking->starts_at,
            'old_ends_at' => $booking->ends_at,
            'new_starts_at' => $newStart,
            'new_ends_at' => $newEnd,
            'status' => 'pending',
            'reason' => $reason,
        ]);
        $this->log($booking, $actor, 'reschedule_requested', [
            'reschedule_id' => $reschedule->id,
            'new_starts_at' => $newStart->toIso8601String(),
        ]);
        $this->notify('booking_reschedule_requested', [$booking->cfm_id], $booking, [
            'participant_name' => $actor->name,
            'session_time' => $newStart->timezone($booking->timezone)->format('M j, Y g:i A'),
        ]);

        return $reschedule;
    }

    public function resolveReschedule(BookingReschedule $reschedule, User $actor, bool $approve, ?string $reason = null): BookingReschedule
    {
        $booking = $reschedule->booking;
        abort_unless($booking->cfm_id === $actor->id && $actor->can('reschedule bookings'), 403);
        abort_unless($reschedule->status === 'pending', 422, 'This reschedule request has already been resolved.');
        abort_unless(in_array($booking->status, ['pending_approval', 'confirmed'], true), 422, 'The booking is no longer active.');

        return DB::transaction(function () use ($reschedule, $booking, $actor, $approve, $reason): BookingReschedule {
            $locked = BookingReschedule::query()->whereKey($reschedule->id)->lockForUpdate()->firstOrFail();
            if ($approve) {
                $this->assertSlotAvailable(
                    $booking->eventType,
                    $booking->link,
                    $locked->new_starts_at,
                    $locked->new_ends_at,
                    $booking,
                    $booking->trainee,
                );
                $booking->forceFill([
                    'starts_at' => $locked->new_starts_at,
                    'ends_at' => $locked->new_ends_at,
                ])->save();
                if ($booking->calendarEvent) {
                    $booking->calendarEvent->forceFill([
                        'starts_at' => $locked->new_starts_at,
                        'ends_at' => $locked->new_ends_at,
                    ])->save();
                }
                $locked->forceFill(['status' => 'approved', 'resolved_at' => now()])->save();
            } else {
                $locked->forceFill(['status' => 'declined', 'resolved_at' => now()])->save();
            }

            $this->log($booking, $actor, $approve ? 'reschedule_approved' : 'reschedule_declined', [
                'reschedule_id' => $locked->id,
                'reason' => $reason,
            ]);
            $this->notify('booking_reschedule_decision', [$locked->requested_by], $booking, [
                'decision' => $approve ? 'approved' : 'declined',
            ]);

            return $locked->fresh();
        });
    }

    public function complete(Booking $booking, User $actor): Booking
    {
        abort_unless($booking->cfm_id === $actor->id && $actor->can('manage mentor sessions'), 403);
        abort_unless($booking->status === 'confirmed' && $booking->ends_at->isPast(), 422, 'Only past confirmed bookings can be completed.');
        $booking->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
        $this->log($booking, $actor, 'booking_completed');

        return $booking->fresh();
    }

    public function markNoShow(Booking $booking, User $actor): Booking
    {
        abort_unless($booking->cfm_id === $actor->id && $actor->can('manage mentor sessions'), 403);
        abort_unless($booking->status === 'confirmed' && $booking->ends_at->isPast(), 422, 'Only past confirmed bookings can be marked as no-show.');
        $booking->forceFill(['status' => 'no_show', 'no_show_at' => now()])->save();
        $this->log($booking, $actor, 'booking_no_show');

        return $booking->fresh();
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        abort_unless($actor->can('cancel', $booking), 403);
        abort_unless(in_array($booking->status, ['pending_approval', 'confirmed'], true), 422, 'This booking can no longer be cancelled.');

        DB::transaction(function () use ($booking, $actor, $reason): void {
            $booking->forceFill(['status' => 'cancelled', 'cancelled_at' => now()])->save();
            BookingCancellation::query()->create([
                'booking_id' => $booking->id,
                'cancelled_by' => $actor->id,
                'cancelled_by_type' => $booking->cfm_id === $actor->id ? 'cfm' : 'trainee',
                'reason' => $reason,
            ]);
            if ($booking->calendarEvent) {
                $booking->calendarEvent->forceFill(['status' => 'cancelled'])->save();
            }
            $this->log($booking, $actor, 'booking_cancelled', ['reason' => $reason]);
            $recipientId = $booking->cfm_id === $actor->id ? $booking->trainee_id : $booking->cfm_id;
            $this->notify('booking_cancelled', [$recipientId], $booking, [
                'actor_name' => $actor->name,
            ]);
        });

        return $booking->fresh();
    }

    private function scheduleFor(BookingEventType $eventType, ?BookingLink $link): AvailabilitySchedule
    {
        $schedule = $link?->schedule;
        if ($schedule && $schedule->user_id === $eventType->owner_id && $schedule->is_active) {
            return $schedule;
        }

        return AvailabilitySchedule::query()
            ->where('user_id', $eventType->owner_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->firstOrFail();
    }

    private function assertCanBook(BookingEventType $eventType, ?BookingLink $link, ?User $trainee): void
    {
        abort_unless($eventType->is_active && (! $link || $eventType->owner_id === $link->owner_id), 404);
        if ($link) {
            abort_unless($link->is_active && ! $link->trashed(), 404);
            abort_if($link->expires_at?->isPast(), 410, 'This booking link has expired.');
            abort_if($link->is_one_time && $link->used_at, 410, 'This booking link has already been used.');
            abort_if($link->max_uses !== null && $link->uses_count >= $link->max_uses, 410, 'This booking link has reached its booking limit.');
            abort_unless($link->booking_event_type_id === null || $link->booking_event_type_id === $eventType->id, 404);
        }

        abort_unless(in_array($eventType->visibility, ['public', 'assigned_apprentices', 'private'], true), 403);
        if ($eventType->visibility === 'private') {
            abort_unless($link && in_array($link->visibility, ['private', 'invite_only'], true), 403);
        }

        if ($link?->apprentice_id) {
            abort_unless($trainee?->id === $link->apprentice_id, 403, 'This link is reserved for its assigned apprentice.');
        }

        if ($trainee) {
            abort_unless($trainee->can('book mentor sessions'), 403, 'You do not have permission to book mentor sessions.');
        }

        if (! $trainee) {
            abort_unless($eventType->visibility === 'public' && $link?->apprentice_id === null, 403, 'Sign in with your assigned trainee account to book this session.');
        }

        if ($eventType->visibility === 'assigned_apprentices' && $trainee) {
            $assigned = $trainee->mentor_id === $eventType->owner_id
                || DB::table('mentor_assignments')
                    ->where('mentor_id', $eventType->owner_id)
                    ->where('apprentice_id', $trainee->id)
                    ->where('status', 'active')
                    ->exists();
            abort_unless($assigned             || $link?->apprentice_id === $trainee->id, 403, 'This mentor only accepts bookings from assigned apprentices.');
        }
    }

    private function assertSlotAvailable(
        BookingEventType $eventType,
        ?BookingLink $link,
        CarbonInterface $start,
        CarbonInterface $end,
        ?Booking $ignore = null,
        ?User $trainee = null,
    ): void {
        $date = Carbon::parse($start)->timezone($this->scheduleFor($eventType, $link)->timezone);
        $available = collect($this->availableSlots($eventType, $link, $date, $trainee, $ignore))
            ->contains(fn (array $slot): bool => Carbon::parse($slot['starts_at'])->equalTo($start));
        if (! $available) {
            throw ValidationException::withMessages(['slot' => 'That time is no longer available. Please choose another slot.']);
        }

        if ($this->conflicts($eventType, $this->scheduleFor($eventType, $link), $start, $end, $ignore, $trainee)) {
            throw ValidationException::withMessages(['slot' => 'That time conflicts with another appointment. Please choose another slot.']);
        }
    }

    private function conflicts(
        BookingEventType $eventType,
        AvailabilitySchedule $schedule,
        CarbonInterface $start,
        CarbonInterface $end,
        ?Booking $ignore = null,
        ?User $trainee = null,
    ): bool {
        $bufferedStart = Carbon::parse($start)->copy()->subMinutes($eventType->buffer_before_minutes);
        $bufferedEnd = Carbon::parse($end)->copy()->addMinutes($eventType->buffer_after_minutes);

        $bookingConflict = Booking::query()
            ->where(function (Builder $query) use ($eventType, $trainee): void {
                $query->where('cfm_id', $eventType->owner_id);
                if ($trainee) {
                    $query->orWhere('trainee_id', $trainee->id);
                }
            })
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($ignore, fn (Builder $query) => $query->where('id', '!=', $ignore->id))
            ->where('starts_at', '<', $bufferedEnd)
            ->where('ends_at', '>', $bufferedStart)
            ->exists();

        if ($bookingConflict) {
            return true;
        }

        return CalendarEvent::query()
            ->where(function (Builder $query) use ($eventType, $trainee): void {
                $query->where('organizer_id', $eventType->owner_id);
                if ($trainee) {
                    $query->orWhereHas('attendees', fn (Builder $attendees) => $attendees->where('user_id', $trainee->id));
                }
            })
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->when($ignore?->calendar_event_id, fn (Builder $query) => $query->where('id', '!=', $ignore->calendar_event_id))
            ->where('starts_at', '<', $bufferedEnd)
            ->where('ends_at', '>', $bufferedStart)
            ->exists();
    }

    private function activeBookingQuery(int $ownerId, ?int $traineeId = null, ?string $ignoreId = null): Builder
    {
        return Booking::query()
            ->where(function (Builder $query) use ($ownerId, $traineeId): void {
                $query->where('cfm_id', $ownerId);
                if ($traineeId) {
                    $query->orWhere('trainee_id', $traineeId);
                }
            })
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId));
    }

    private function isBlackout(int $ownerId, CarbonInterface $date): bool
    {
        $blackouts = BlackoutDate::query()
            ->where('user_id', $ownerId)
            ->get(['starts_on', 'ends_on', 'is_recurring']);

        foreach ($blackouts as $blackout) {
            if ($blackout->is_recurring) {
                $originalStart = $blackout->starts_on;
                $originalEnd = $blackout->ends_on ?? $originalStart;
                $start = $originalStart->copy()->setYear($date->year);
                $end = $originalEnd->copy()->setYear($date->year);
                if ($end->lt($start)) {
                    $end->addYear();
                }
                if ($date->lt($start)) {
                    $start->subYear();
                    $end->subYear();
                    if ($end->lt($start)) {
                        $end->addYear();
                    }
                }
            } else {
                $start = $blackout->starts_on;
                $end = $blackout->ends_on ?? $start;
            }

            if ($date->betweenIncluded($start->startOfDay(), $end->endOfDay())) {
                return true;
            }
        }

        return false;
    }

    private function validateAnswers(BookingEventType $eventType, array $answers): void
    {
        $questions = $eventType->questions()->where('is_active', true)->get();
        foreach ($questions as $question) {
            $answer = $answers[$question->id] ?? null;
            if ($question->is_required && blank($answer)) {
                throw ValidationException::withMessages(["answers.{$question->id}" => "The {$question->question} field is required."]);
            }
            if ($answer !== null && $answer !== '' && $question->type === 'multiple_choice' && ! in_array((string) $answer, $question->options ?? [], true)) {
                throw ValidationException::withMessages(["answers.{$question->id}" => 'Choose one of the listed answers.']);
            }
            if ($answer !== null && $answer !== '' && $question->type === 'yes_no' && ! in_array(strtolower((string) $answer), ['yes', 'no'], true)) {
                throw ValidationException::withMessages(["answers.{$question->id}" => 'Choose yes or no.']);
            }
        }

        $knownIds = $questions->modelKeys();
        foreach (array_keys($answers) as $questionId) {
            if (! in_array((int) $questionId, $knownIds, true)) {
                throw ValidationException::withMessages(['answers' => 'One or more answers do not belong to this booking type.']);
            }
        }
    }

    private function syncCalendarEvent(Booking $booking, BookingEventType $eventType): void
    {
        if ($booking->calendar_event_id) {
            $booking->calendarEvent()->update([
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
                'status' => 'scheduled',
            ]);

            return;
        }

        $calendarType = CalendarEventType::query()->where('slug', 'cfm-booking-session')->first();
        $event = CalendarEvent::query()->create([
            'calendar_event_type_id' => $calendarType?->id,
            'calendar_category_id' => $eventType->calendar_category_id,
            'organizer_id' => $booking->cfm_id,
            'title' => $eventType->title.' - '.($booking->trainee?->name ?? $booking->attendees()->value('name') ?? 'Guest'),
            'description' => 'Auto-created from confirmed mentor booking.',
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
            'timezone' => $booking->timezone,
            'location' => $booking->location_details,
            'meeting_link' => $booking->meeting_link,
            'visibility' => 'shared_team',
            'status' => 'scheduled',
            'color' => $eventType->color,
            'related_apprentice_id' => $booking->trainee_id,
        ]);

        foreach ([$booking->cfm_id, $booking->trainee_id] as $userId) {
            if (! $userId) {
                continue;
            }
            CalendarEventAttendee::query()->create([
                'calendar_event_id' => $event->id,
                'user_id' => $userId,
                'attendee_type' => 'user',
                'rsvp_status' => 'accepted',
                'responded_at' => now(),
            ]);
            foreach ([5, 15, 30, 60, 1440] as $minutesBefore) {
                CalendarEventReminder::query()->create([
                    'calendar_event_id' => $event->id,
                    'user_id' => $userId,
                    'minutes_before' => $minutesBefore,
                    'channel' => 'in_app',
                ]);
            }

        }

        if (! $booking->trainee_id) {
            $guest = $booking->attendees()->whereNull('user_id')->first();
            if ($guest) {
                CalendarEventAttendee::query()->create([
                    'calendar_event_id' => $event->id,
                    'name' => $guest->name,
                    'email' => $guest->email,
                    'attendee_type' => 'guest',
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]);
            }
        }

        $booking->forceFill(['calendar_event_id' => $event->id])->save();
    }

    private function log(Booking $booking, User $actor, string $action, array $payload = []): void
    {
        BookingActivityLog::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $actor->id,
            'action' => $action,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  list<int|null>  $recipientIds
     * @param  array<string, string>  $details
     */
    private function notify(string $trigger, array $recipientIds, Booking $booking, array $details = []): void
    {
        $recipientIds = array_values(array_unique(array_filter($recipientIds)));
        if ($recipientIds === []) {
            return;
        }

        app(NotificationOrchestrator::class)->dispatch($trigger, [
            'queue' => false,
            'recipients' => $recipientIds,
            'module' => 'booking',
            'priority' => 'medium',
            'related' => ['type' => Booking::class, 'id' => $booking->id],
            'related_user_id' => $booking->trainee_id,
            'template_data' => [
                'event_title' => $booking->eventType?->title ?? 'Mentor session',
                'session_time' => $booking->starts_at->timezone($booking->timezone)->format('M j, Y g:i A'),
                ...$details,
            ],
            'action_link' => [
                'route' => 'bookings.my',
                'label' => 'View appointment',
            ],
        ]);
    }
}
