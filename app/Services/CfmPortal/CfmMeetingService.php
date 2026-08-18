<?php

namespace App\Services\CfmPortal;

use App\Models\Booking;
use App\Models\CfmMeeting;
use App\Models\CfmMeetingNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CfmMeetingService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, string $statusFilter = 'upcoming'): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $meetings = CfmMeeting::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with(['latestNote.author', 'booking.eventType'])
            ->orderByDesc('starts_at')
            ->get();

        $linkedBookingIds = $meetings->pluck('booking_id')->filter()->all();

        $unlinkedBookings = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->whereNull('cancelled_at')
            ->whereNotIn('id', $linkedBookingIds)
            ->where('starts_at', '>=', now()->subDays(90))
            ->with('eventType')
            ->orderBy('starts_at')
            ->get();

        $filtered = $this->filterMeetings($meetings, $statusFilter);

        return [
            'key' => 'meetings',
            'title' => 'Meetings & Sessions',
            'description' => 'Schedule coaching sessions, track calendar bookings, and capture meeting notes and action items.',
            'stats' => [
                'upcoming' => $meetings->filter(fn (CfmMeeting $meeting) => $meeting->isUpcoming())->count(),
                'completed' => $meetings->where('status', 'completed')->count(),
                'scheduled' => $meetings->where('status', 'scheduled')->count(),
                'calendar_bookings' => $unlinkedBookings->where('starts_at', '>=', now())->count(),
            ],
            'meetings' => $filtered->map(fn (CfmMeeting $meeting) => $this->meetingRow($meeting))->values()->all(),
            'unlinked_bookings' => $unlinkedBookings->map(fn (Booking $booking) => $this->bookingRow($booking))->values()->all(),
            'types' => CfmMeeting::TYPES,
            'statuses' => CfmMeeting::STATUSES,
            'status_filter' => $statusFilter,
            'calendar_url' => route('calendar.index'),
            'bookings_url' => route('bookings.dashboard'),
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    public function create(User $cfm, User $trainee, User $actor, array $data): CfmMeeting
    {
        $this->assertTraineeAccess($cfm, $trainee);

        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = isset($data['ends_at']) && $data['ends_at']
            ? Carbon::parse($data['ends_at'])
            : $startsAt->copy()->addHour();

        return CfmMeeting::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'booking_id' => $data['booking_id'] ?? null,
            'type' => $data['type'] ?? 'coaching',
            'title' => $data['title'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
        ]);
    }

    public function importFromBooking(User $cfm, User $trainee, User $actor, Booking $booking): CfmMeeting
    {
        $this->assertTraineeAccess($cfm, $trainee);

        if ((int) $booking->cfm_id !== (int) $cfm->id || (int) $booking->trainee_id !== (int) $trainee->id) {
            abort(403);
        }

        if (CfmMeeting::query()->where('booking_id', $booking->id)->exists()) {
            throw ValidationException::withMessages(['booking' => 'This booking is already linked to a meeting.']);
        }

        $type = match (true) {
            str_contains(strtolower($booking->eventType?->name ?? ''), 'licens') => 'licensing',
            str_contains(strtolower($booking->eventType?->name ?? ''), 'fap') => 'fap_review',
            str_contains(strtolower($booking->eventType?->name ?? ''), 'onboard') => 'onboarding',
            default => 'coaching',
        };

        return CfmMeeting::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'booking_id' => $booking->id,
            'type' => $type,
            'title' => $booking->eventType?->name ?? 'Mentor session',
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
            'status' => $booking->completed_at ? 'completed' : 'scheduled',
        ]);
    }

    public function updateStatus(User $cfm, CfmMeeting $meeting, User $actor, string $status): CfmMeeting
    {
        $this->assertMeetingAccess($cfm, $meeting);

        if (! in_array($status, CfmMeeting::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid meeting status.']);
        }

        $meeting->update(['status' => $status]);

        return $meeting->refresh();
    }

    public function saveNotes(User $cfm, CfmMeeting $meeting, User $actor, array $data): CfmMeetingNote
    {
        $this->assertMeetingAccess($cfm, $meeting);

        $note = CfmMeetingNote::query()->create([
            'cfm_meeting_id' => $meeting->id,
            'summary' => $data['summary'] ?? null,
            'action_items' => $this->parseActionItems($data['action_items'] ?? null),
            'created_by' => $actor->id,
        ]);

        if ($meeting->status === 'scheduled' && $meeting->starts_at?->isPast()) {
            $meeting->update(['status' => 'completed']);
        }

        return $note;
    }

    public function delete(User $cfm, CfmMeeting $meeting): void
    {
        $this->assertMeetingAccess($cfm, $meeting);
        $meeting->delete();
    }

    public function findForCfm(User $cfm, int $meetingId): CfmMeeting
    {
        return CfmMeeting::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($meetingId)
            ->firstOrFail();
    }

    public function findBookingForCfm(User $cfm, int $bookingId): Booking
    {
        return Booking::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($bookingId)
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, CfmMeeting>  $meetings
     * @return Collection<int, CfmMeeting>
     */
    private function filterMeetings(Collection $meetings, string $filter): Collection
    {
        return match ($filter) {
            'upcoming' => $meetings->filter(fn (CfmMeeting $meeting) => $meeting->status === 'scheduled' && $meeting->starts_at?->isFuture())->values(),
            'past' => $meetings->filter(fn (CfmMeeting $meeting) => in_array($meeting->status, ['completed', 'cancelled', 'no_show'], true) || ($meeting->starts_at?->isPast() && $meeting->status !== 'scheduled'))->values(),
            default => $meetings->values(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function meetingRow(CfmMeeting $meeting): array
    {
        $note = $meeting->latestNote;

        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'type' => $meeting->type,
            'type_label' => $meeting->typeLabel(),
            'status' => $meeting->status,
            'starts_at' => $meeting->starts_at?->format('M j, Y g:i A') ?? '—',
            'ends_at' => $meeting->ends_at?->format('g:i A') ?? '—',
            'is_upcoming' => $meeting->isUpcoming(),
            'from_booking' => (bool) $meeting->booking_id,
            'booking_status' => $meeting->booking?->status,
            'meeting_link' => $meeting->booking?->meeting_link,
            'note_summary' => $note?->summary,
            'action_items' => $note?->action_items ?? [],
            'note_author' => $note?->author?->name,
            'noted_at' => $note?->created_at?->format('M j, Y g:i A'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingRow(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'title' => $booking->eventType?->name ?? 'Booked session',
            'starts_at' => $booking->starts_at?->format('M j, Y g:i A') ?? '—',
            'ends_at' => $booking->ends_at?->format('g:i A') ?? '—',
            'status' => $booking->status,
            'meeting_link' => $booking->meeting_link,
        ];
    }

    /**
     * @return list<string>
     */
    private function parseActionItems(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function assertTraineeAccess(User $cfm, User $trainee): void
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }
    }

    private function assertMeetingAccess(User $cfm, CfmMeeting $meeting): void
    {
        if ((int) $meeting->cfm_id !== (int) $cfm->id) {
            abort(403);
        }
    }
}
