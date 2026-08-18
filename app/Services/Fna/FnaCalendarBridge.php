<?php

namespace App\Services\Fna;

use App\Events\Fna\FnaMeetingScheduled;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\FnaRecord;
use App\Models\ProspectAppointment;
use App\Models\User;
use App\Services\Prospects\ProspectCalendarBridge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FnaCalendarBridge
{
    public function __construct(
        private FnaWorkflowService $workflow,
        private FnaTaskBridge $tasks,
        private ProspectCalendarBridge $prospectCalendar,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleMeeting(FnaRecord $fna, User $organizer, array $data): CalendarEvent
    {
        $fna->loadMissing(['prospect', 'cfm', 'owner']);

        $typeSlug = $data['meeting_type'] ?? 'fna-client-meeting';
        $eventType = CalendarEventType::query()->where('slug', $typeSlug)->where('is_active', true)->first()
            ?? CalendarEventType::query()->where('slug', 'prospect-appointment')->first();

        $category = CalendarCategory::query()->where('slug', 'prospects')->first();

        $startsAt = $data['starts_at'] ?? now()->addDays(3);
        if (! $startsAt instanceof \Carbon\CarbonInterface) {
            $startsAt = Carbon::parse($startsAt);
        }
        $durationMinutes = (int) ($data['duration_minutes'] ?? 60);
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        [$location, $meetingLink] = $this->splitLocationOrLink($data['location_or_link'] ?? $data['location'] ?? $data['meeting_link'] ?? null);

        $title = $data['title'] ?? $this->buildTitle($fna, $eventType?->name ?? 'FNA Meeting');

        $payload = [
            'calendar_event_type_id' => $eventType?->id,
            'calendar_category_id' => $category?->id ?? $eventType?->calendar_category_id,
            'organizer_id' => $organizer->id,
            'title' => $title,
            'description' => $data['description'] ?? $this->buildDescription($fna),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => $data['timezone'] ?? config('app.timezone', 'America/Vancouver'),
            'location' => $location,
            'meeting_link' => $meetingLink,
            'visibility' => 'private',
            'status' => 'scheduled',
            'related_prospect_id' => $fna->prospect_id,
            'related_fna_id' => $fna->id,
        ];

        if ($fna->calendar_event_id) {
            $event = CalendarEvent::query()->findOrFail($fna->calendar_event_id);
            $event->update($payload);
        } else {
            $event = CalendarEvent::create($payload);
        }

        $fna->update(['calendar_event_id' => $event->id]);

        $this->syncAttendees($event, $fna, $organizer);

        if ($fna->prospect_id) {
            $this->syncProspectAppointment($fna, $event, $organizer, $data);
        }

        if (in_array($fna->status, ['approved_by_cfm', 'follow_up_needed'], true)) {
            $fna = $this->workflow->transition($fna, $organizer, 'scheduled_for_client_review');
        }

        $followUpTemplate = config('fna.task_templates.meeting_scheduled') ?? config('fna.task_templates.approved');
        if (is_array($followUpTemplate)) {
            $this->tasks->createFromTemplate($fna->fresh(), $organizer, $followUpTemplate, $fna->owner);
        }

        event(new FnaMeetingScheduled($fna->fresh(), $organizer, $event));

        return $event->fresh(['type', 'category', 'attendees']);
    }

    public function markMeetingCompleted(FnaRecord $fna, User $actor): FnaRecord
    {
        if ($fna->calendar_event_id) {
            CalendarEvent::query()
                ->where('id', $fna->calendar_event_id)
                ->update(['status' => 'completed']);
        }

        if ($fna->status === 'scheduled_for_client_review') {
            return $this->workflow->transition($fna, $actor, 'presented_to_prospect');
        }

        return $fna->fresh();
    }

    public function cancelMeeting(FnaRecord $fna): void
    {
        if (! $fna->calendar_event_id) {
            return;
        }

        CalendarEvent::query()
            ->where('id', $fna->calendar_event_id)
            ->update(['status' => 'cancelled']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncProspectAppointment(FnaRecord $fna, CalendarEvent $event, User $organizer, array $data): void
    {
        $appointmentTypeId = \App\Models\AppointmentType::query()
            ->where('slug', 'financial-needs-analysis')
            ->value('id');

        $appointment = ProspectAppointment::query()
            ->where('prospect_id', $fna->prospect_id)
            ->where('calendar_event_id', $event->id)
            ->first();

        $payload = [
            'prospect_id' => $fna->prospect_id,
            'owner_id' => $organizer->id,
            'appointment_type_id' => $appointmentTypeId,
            'calendar_event_id' => $event->id,
            'scheduled_at' => $event->starts_at,
            'timezone' => $event->timezone,
            'location_or_link' => $event->meeting_link ?: $event->location,
            'status' => 'scheduled',
            'purpose' => $data['purpose'] ?? "FNA review — {$fna->reference_code}",
            'notes' => $data['notes'] ?? null,
        ];

        if ($appointment) {
            $appointment->update($payload);
        } else {
            $appointment = ProspectAppointment::create($payload);
        }

        $this->prospectCalendar->pushAppointment($appointment->fresh(['prospect', 'type', 'owner']));
    }

    protected function syncAttendees(CalendarEvent $event, FnaRecord $fna, User $organizer): void
    {
        CalendarEventAttendee::query()
            ->where('calendar_event_id', $event->id)
            ->whereIn('attendee_type', ['cfm', 'prospect'])
            ->delete();

        if ($fna->cfm_user_id && $fna->cfm_user_id !== $organizer->id) {
            CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'user_id' => $fna->cfm_user_id,
                'name' => $fna->cfm?->name,
                'email' => $fna->cfm?->email,
                'attendee_type' => 'cfm',
                'rsvp_status' => 'pending',
            ]);
        }

        if ($fna->prospect_id) {
            CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'prospect_id' => $fna->prospect_id,
                'name' => $fna->client_name,
                'email' => $fna->client_email,
                'attendee_type' => 'prospect',
                'rsvp_status' => 'pending',
            ]);
        }
    }

    protected function buildTitle(FnaRecord $fna, string $typeName): string
    {
        return "FNA: {$typeName} — {$fna->client_name}";
    }

    protected function buildDescription(FnaRecord $fna): string
    {
        $parts = array_filter([
            "FNA Reference: {$fna->reference_code}",
            $fna->recommended_next_action,
            $fna->main_needs_identified,
        ]);

        return implode("\n\n", $parts);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function splitLocationOrLink(?string $locationOrLink): array
    {
        if ($locationOrLink === null || trim($locationOrLink) === '') {
            return [null, null];
        }

        if (Str::startsWith(strtolower(trim($locationOrLink)), ['http://', 'https://', 'zoom.', 'www.'])) {
            return [null, trim($locationOrLink)];
        }

        return [trim($locationOrLink), null];
    }
}
