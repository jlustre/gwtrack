# Calendar & Events Module

## Purpose

The Calendar & Events module gives GWTrack a Google/Outlook-style scheduling center for team events, trainings, prospect appointments, licensing deadlines, mentor sessions, Field Apprenticeship Program sessions, CFM certification, rank reviews, recognition, and organization-wide events.

## Implemented Scaffold

- Calendar routes for month, week, day, agenda, settings, event detail, and export.
- Calendar UI with fixed workspace layout: left controls, center calendar grid, right upcoming-events panel, and mobile floating create button.
- Calendar database schema with categories, event types, events, attendees, reminders, recurrences, attachments, visibility rules, notes, activity logs, and user preferences.
- Calendar models and relationships.
- Calendar policies for event visibility, attendees, reminders, visibility rules, and export.
- Calendar seed data for realistic GWTrack scenarios.
- Livewire component stubs for future modal and real-time interactions.

## Database Tables

- `calendar_categories`
- `calendar_event_types`
- `calendar_events`
- `calendar_event_attendees`
- `calendar_event_reminders`
- `calendar_event_recurrences`
- `calendar_event_attachments`
- `calendar_event_visibility_rules`
- `calendar_event_notes`
- `calendar_event_activity_logs`
- `user_calendar_preferences`

## Access Rules

- Members can view their calendar, create personal events, edit/delete their own events, view shared events, invite attendees, and manage prospect appointments.
- Trainers can manage training calendar events.
- Certified Field Mentors can manage mentor sessions.
- Team Leaders can manage team calendar events, licensing reviews, prospect appointments, mentor sessions, and rank reviews.
- Agency Owners, Admins, and Super Admins can manage broader calendar visibility and organization calendar events.
- Private events remain visible only to the organizer, invited attendees, or users with private-calendar authority.

## Calendar Permissions

- `view calendar`
- `create calendar events`
- `edit own calendar events`
- `delete own calendar events`
- `manage team calendar`
- `manage training calendar`
- `manage licensing calendar`
- `manage prospect appointments`
- `manage mentor sessions`
- `manage rank review events`
- `view shared calendar events`
- `invite attendees`
- `manage event visibility`
- `view private events`
- `manage organization calendar`

## Routes

- `GET /calendar`
- `GET /calendar/month`
- `GET /calendar/week`
- `GET /calendar/day`
- `GET /calendar/agenda`
- `GET /calendar/events/{event}`
- `GET /calendar/settings`
- `GET /calendar/export`
- `GET /events` as an events/agenda alias

## Livewire Component Plan

- `CalendarPage`
- `CalendarHeader`
- `CalendarSidebar`
- `MiniCalendar`
- `MonthView`
- `WeekView`
- `WorkWeekView`
- `DayView`
- `AgendaView`
- `UpcomingEventsPanel`
- `EventCreateModal`
- `EventEditModal`
- `EventDetailsPanel`
- `EventDeleteModal`
- `EventFilters`
- `EventSearch`
- `EventAttendeeManager`
- `EventReminderManager`
- `RecurrenceRuleBuilder`
- `EventVisibilityManager`
- `ProspectAppointmentForm`
- `MentorSessionForm`
- `TrainingEventForm`
- `RankReviewEventForm`
- `CalendarSettingsModal`

## Next Development Steps

- Build the create/edit event modal with validation and permission-aware fields.
- Add RSVP actions for attendees.
- Add reminder delivery through notifications and email.
- Add recurring event expansion for future instances.
- Connect Prospect Appointment records to `calendar_events`.
- Connect FAP, CFM Training, Licensing, and Rank Advancement workflows to automatic calendar event creation.
- Connect confirmed CFM booking sessions to automatic `calendar_events` and attendees.
- Add drag-and-drop event rescheduling in month/week/day views.
- Add ICS export and external calendar subscription support.
