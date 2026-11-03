# CFM Mentor Scheduling And Booking Module

## Purpose

The CFM Mentor Scheduling module adds Calendly/Cal.com-style booking to GWTracks. Certified Field Mentors can publish availability, create booking event types, share booking links, accept or auto-confirm trainee sessions, and connect confirmed sessions to the existing Calendar & Events module.

## Architecture

- `booking_event_types` define CFM-owned session templates such as Initial Mentor Call, Field Apprenticeship Session, Prospect Call Support, Practice Presentation, Licensing Study Session, Rank Advancement Coaching, Weekly Progress Review, and Final Apprenticeship Review.
- `availability_schedules`, `availability_rules`, `availability_overrides`, and `blackout_dates` represent timezone-aware weekly availability, exceptions, vacations, and unavailable periods.
- `booking_links` represent personal CFM pages, event-type links, apprentice-specific links, team links, private invite links, one-time links, and expiring links.
- `bookings` represent pending, confirmed, declined, rescheduled, cancelled, completed, no-show, and expired booking records.
- `booking_attendees`, `booking_questions`, `booking_answers`, `booking_reschedules`, `booking_cancellations`, and `booking_activity_logs` preserve workflow state and audit history.
- Confirmed bookings can create `calendar_events` and `calendar_event_attendees` so sessions appear in the CFM calendar, trainee calendar, dashboard reminders, and upcoming events.

## Booking Workflow

1. CFM creates an availability schedule.
2. CFM creates booking event types with duration, buffers, approval mode, booking limits, location rules, and custom questions.
3. CFM shares `/book/{username}`, `/book/{username}/{eventTypeSlug}`, `/book/mentor/{mentorSlug}`, or `/book/invite/{token}`.
4. Trainee selects an available slot and answers booking questions.
5. System validates mentor assignment, booking window, minimum notice, buffers, daily/weekly limits, blackout dates, and calendar conflicts.
6. Auto-confirmed bookings immediately create calendar events and attendees for both CFM and trainee.
7. Approval-required bookings enter `pending_approval`; the CFM can approve or decline from Booking Requests.
8. Reschedules and cancellations preserve history and notify both users.
9. CFM can mark past confirmed bookings completed or no-show.

## Implemented

- Timezone-aware slot generation uses weekly availability, date overrides, blackout dates, event duration and buffers, minimum notice, booking horizon, daily/weekly limits, active bookings, and calendar conflicts.
- Public and invite booking pages validate active, expired, one-time, and use-limited links. Public event pages are restricted to public or assigned-apprentice event types; private event types require a private/invite link.
- Booking submission supports guest contact details, trainee identity, reason/topics, required questions, multiple-choice validation, and yes/no validation.
- CFM booking screens provide schedule, weekly rule, date override, blackout, event type, custom question, and booking link creation, plus booking approval/decline, cancellation, reschedule decisions, completion, and no-show actions.
- Confirmed bookings create/update calendar events, user/guest attendees, and in-app reminders at 5, 15, 30, 60, and 1,440 minutes before the session. Cancelled calendar events are excluded from reminder dispatch.
- Booking lifecycle changes are recorded in activity/history tables and booking notifications are dispatched through the existing notification module.
- Feature coverage is in `tests/Feature/BookingSchedulingModuleTest.php`.

## Conflict Detection Plan

- Check CFM `calendar_events` overlapping the requested time plus buffer windows.
- Check trainee `calendar_events` overlapping the requested time.
- Check active `bookings` with statuses `pending_approval` or `confirmed`.
- Validate requested time against `availability_rules`, `availability_overrides`, and `blackout_dates`.
- Enforce `minimum_notice_minutes`, `maximum_booking_days_ahead`, `daily_booking_limit`, and `weekly_booking_limit`.
- Reject unrelated trainee bookings unless a `mentor_assignment`, team permission, private invite, or admin permission grants access.

## Authorization Rules

- CFMs manage their own availability, event types, links, requests, and apprentice bookings.
- Trainees can book with their assigned CFM.
- Trainees cannot book unrelated CFMs unless a private invite or team permission allows it.
- Agency owners and admins can view team booking activity.
- Booking visibility must respect sponsor hierarchy and mentor assignment rules.

## Permissions

- `view booking dashboard`
- `manage own availability`
- `manage own booking event types`
- `create booking links`
- `view own bookings`
- `book mentor sessions`
- `approve booking requests`
- `decline booking requests`
- `reschedule bookings`
- `cancel bookings`
- `manage team bookings`
- `view apprentice bookings`
- `manage booking settings`

## Livewire Component Structure

- `BookingsHub` supplies the CFM dashboard, availability, event types, links, requests, appointments, calendar, and settings panels.
- `PublicBookingPage` provides public/invite event selection, slot picking, question answers, and booking submission.
- `MyBookingsList` displays a user's bookings and supports cancellation and reschedule requests.
- `BookingWorkflowService` owns slot generation, validation, booking lifecycle transitions, calendar synchronization, audit history, and notification dispatch.

## Calendar Event Auto-Creation Snippet

```php
$event = CalendarEvent::create([
    'calendar_event_type_id' => $calendarType->id,
    'calendar_category_id' => $calendarType->calendar_category_id,
    'organizer_id' => $booking->cfm_id,
    'title' => $booking->eventType->title.' - '.$booking->trainee?->name,
    'description' => 'Auto-created from confirmed mentor booking.',
    'starts_at' => $booking->starts_at,
    'ends_at' => $booking->ends_at,
    'timezone' => $booking->timezone,
    'meeting_link' => $booking->meeting_link,
    'visibility' => 'shared_team',
    'status' => 'scheduled',
    'color' => $booking->eventType->color,
    'related_apprentice_id' => $booking->trainee_id,
]);

foreach ([$booking->cfm_id, $booking->trainee_id] as $userId) {
    CalendarEventAttendee::create([
        'calendar_event_id' => $event->id,
        'user_id' => $userId,
        'attendee_type' => 'user',
        'rsvp_status' => 'accepted',
        'responded_at' => now(),
    ]);
}

$booking->update([
    'calendar_event_id' => $event->id,
    'status' => 'confirmed',
    'confirmed_at' => now(),
]);
```

## Next Development Steps

- Add event-type editing and richer booking-link management (edit/revoke, copy/share actions, and explicit usage history).
- Add booking reports for completion/no-show rate, lead time, CFM utilization, and apprenticeship step completion.
- Add guest-facing confirmation, cancellation, and reminder delivery; current in-app notifications target authenticated users.
- Connect completed-session outcomes to Field Apprenticeship progress if that workflow is desired.
