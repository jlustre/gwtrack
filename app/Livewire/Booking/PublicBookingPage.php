<?php

namespace App\Livewire\Booking;

use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\User;
use App\Services\BookingWorkflowService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class PublicBookingPage extends Component
{
    public ?int $mentorId = null;

    public ?string $eventTypeSlug = null;

    public ?string $inviteToken = null;

    public ?int $eventTypeId = null;

    public string $date = '';

    public string $selectedSlot = '';

    public string $guestName = '';

    public string $guestEmail = '';

    public string $reason = '';

    public string $topics = '';

    /** @var array<int, mixed> */
    public array $answers = [];

    public ?string $confirmation = null;

    public function mount(?int $mentorId = null, ?string $eventTypeSlug = null, ?string $inviteToken = null): void
    {
        $this->mentorId = $mentorId;
        $this->eventTypeSlug = $eventTypeSlug;
        $this->inviteToken = $inviteToken;
        $this->date = now()->addDay()->toDateString();

        $types = $this->eventTypes();
        $this->eventTypeId = $types->firstWhere('slug', $eventTypeSlug)?->id ?? $types->first()?->id;
    }

    public function updatedEventTypeId(): void
    {
        $this->selectedSlot = '';
        $this->answers = [];
    }

    public function updatedDate(): void
    {
        $this->selectedSlot = '';
    }

    public function selectSlot(string $startsAt): void
    {
        $this->selectedSlot = $startsAt;
    }

    public function submit(BookingWorkflowService $workflow): void
    {
        $type = $this->selectedEventType();
        $link = $this->bookingLink($type);
        $this->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'selectedSlot' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'topics' => ['nullable', 'string', 'max:2000'],
            'guestName' => [auth()->check() ? 'nullable' : 'required', 'string', 'max:255'],
            'guestEmail' => [auth()->check() ? 'nullable' : 'required', 'email', 'max:255'],
        ]);

        $booking = $workflow->createBooking(
            $type,
            $link,
            Carbon::parse($this->selectedSlot),
            auth()->user(),
            $this->guestName ?: null,
            $this->guestEmail ?: null,
            $this->answers,
            $this->reason ?: null,
            $this->topics ?: null,
        );

        $this->confirmation = $booking->status === 'confirmed'
            ? 'Your session is confirmed. Check your appointments and calendar for details.'
            : 'Your request was sent to the mentor. You will see it in My Appointments after approval.';
        $this->reset(['selectedSlot', 'answers', 'reason', 'topics']);
    }

    public function render(): View
    {
        $types = $this->eventTypes();
        $selectedType = $types->firstWhere('id', $this->eventTypeId);
        $slots = [];

        if ($selectedType && $this->date !== '') {
            $slots = app(BookingWorkflowService::class)->availableSlots(
                $selectedType,
                $this->bookingLink($selectedType),
                Carbon::parse($this->date),
                auth()->user(),
            );
        }

        return view('livewire.booking.public-booking-page', [
            'mentor' => $this->mentor(),
            'eventTypes' => $types,
            'selectedType' => $selectedType,
            'questions' => $selectedType?->questions()->where('is_active', true)->orderBy('sort_order')->get() ?? collect(),
            'slots' => $slots,
        ]);
    }

    private function eventTypes(): Collection
    {
        $mentor = $this->mentor();
        if (! $mentor) {
            return collect();
        }

        if ($this->inviteToken) {
            $link = $this->bookingLink();
            if (! $link?->eventType || ! $link->eventType->is_active) {
                return collect();
            }

            return collect([$link->eventType->load('questions')]);
        }

        return BookingEventType::query()
            ->where('owner_id', $mentor->id)
            ->where('is_active', true)
            ->whereIn('visibility', ['public', 'assigned_apprentices'])
            ->when($this->eventTypeSlug, fn ($query) => $query->where('slug', $this->eventTypeSlug))
            ->with('questions')
            ->orderBy('title')
            ->get();
    }

    private function selectedEventType(): BookingEventType
    {
        return $this->eventTypes()->firstWhere('id', $this->eventTypeId)
            ?? abort(404);
    }

    private function mentor(): ?User
    {
        if ($this->inviteToken) {
            return $this->bookingLink()?->owner;
        }

        return $this->mentorId ? User::query()->find($this->mentorId) : null;
    }

    private function bookingLink(?BookingEventType $type = null): ?BookingLink
    {
        if ($this->inviteToken) {
            return BookingLink::query()
                ->where('token', $this->inviteToken)
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
                })
                ->where(function ($query): void {
                    $query->where('is_one_time', false)->orWhereNull('used_at');
                })
                ->with(['owner', 'eventType', 'schedule'])
                ->first();
        }

        if (! $this->mentorId || ! $type) {
            return null;
        }

        return BookingLink::query()
            ->where('owner_id', $this->mentorId)
            ->where('booking_event_type_id', $type->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query): void {
                $query->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
            })
            ->where(function ($query): void {
                $query->where('is_one_time', false)->orWhereNull('used_at');
            })
            ->with(['owner', 'eventType', 'schedule'])
            ->first();
    }
}
