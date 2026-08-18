<?php

namespace App\Services\Communication;

use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use Carbon\Carbon;

class AnnouncementEventService
{
    public function __construct(
        private readonly CommunicationHubService $hub,
    ) {}

    /**
     * @param  array<string, mixed>  $eventData
     */
    public function createAndLinkEvent(
        MessageCenterAnnouncement $announcement,
        User $organizer,
        array $eventData,
    ): CalendarEvent {
        $startsAt = Carbon::parse($eventData['starts_at'] ?? now()->addDays(7));
        $durationHours = (int) ($eventData['duration_hours'] ?? config('communication-hub.event_defaults.duration_hours', 1));
        $endsAt = $startsAt->copy()->addHours($durationHours);

        $eventType = CalendarEventType::query()
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->first();

        $event = CalendarEvent::query()->create([
            'calendar_event_type_id' => $eventType?->id,
            'organizer_id' => $organizer->id,
            'title' => $announcement->title,
            'description' => $announcement->summary ?? $announcement->body,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => $organizer->timezone ?? 'America/Vancouver',
            'location' => $eventData['location'] ?? null,
            'meeting_link' => $eventData['meeting_link'] ?? null,
            'visibility' => $eventData['visibility'] ?? config('communication-hub.event_defaults.visibility', 'organization'),
            'status' => 'scheduled',
        ]);

        $announcement->update(['calendar_event_id' => $event->id]);

        return $event;
    }

    public function rsvp(User $user, MessageCenterAnnouncement $announcement, string $status): CalendarEventAttendee
    {
        if (! in_array($status, ['accepted', 'declined', 'pending'], true)) {
            throw new \InvalidArgumentException('Invalid RSVP status.');
        }

        $event = $this->eventForAnnouncement($announcement);

        return CalendarEventAttendee::query()->updateOrCreate(
            [
                'calendar_event_id' => $event->id,
                'user_id' => $user->id,
            ],
            [
                'name' => $user->name,
                'email' => $user->email,
                'attendee_type' => 'user',
                'rsvp_status' => $status,
                'responded_at' => now(),
            ],
        );
    }

    public function register(User $user, MessageCenterAnnouncement $announcement): CalendarEventAttendee
    {
        return $this->rsvp($user, $announcement, 'accepted');
    }

    public function rsvpStatus(User $user, MessageCenterAnnouncement $announcement): ?string
    {
        $eventId = $announcement->calendar_event_id;

        if (! $eventId) {
            return null;
        }

        return CalendarEventAttendee::query()
            ->where('calendar_event_id', $eventId)
            ->where('user_id', $user->id)
            ->value('rsvp_status');
    }

    public function eventForAnnouncement(MessageCenterAnnouncement $announcement): CalendarEvent
    {
        if (! $announcement->calendar_event_id) {
            throw new \InvalidArgumentException('Announcement is not linked to a calendar event.');
        }

        return CalendarEvent::query()->findOrFail($announcement->calendar_event_id);
    }

    /**
     * @return array{event: CalendarEvent|null, rsvp_status: string|null, attendee_count: int}
     */
    public function eventContext(User $user, MessageCenterAnnouncement $announcement): array
    {
        if (! $announcement->calendar_event_id) {
            return ['event' => null, 'rsvp_status' => null, 'attendee_count' => 0];
        }

        $event = CalendarEvent::query()->withCount([
            'attendees as accepted_count' => fn ($query) => $query->where('rsvp_status', 'accepted'),
        ])->find($announcement->calendar_event_id);

        return [
            'event' => $event,
            'rsvp_status' => $this->rsvpStatus($user, $announcement),
            'attendee_count' => (int) ($event->accepted_count ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function publishEventAnnouncement(array $data, User $author): MessageCenterAnnouncement
    {
        $announcement = $this->hub->createDraft([
            'category_code' => 'event',
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'body' => $data['body'],
            'priority' => $data['priority'] ?? 'important',
            'audience_type' => $data['audience_type'] ?? 'all',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ], $author);

        $this->createAndLinkEvent($announcement, $author, $data);
        $this->hub->publish($announcement);

        return $announcement->fresh(['category', 'creator']);
    }
}
