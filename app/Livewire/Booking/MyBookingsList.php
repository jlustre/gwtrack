<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MyBookingsList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'role', except: '')]
    public string $role = '';

    #[Url(as: 'when', except: 'upcoming')]
    public string $when = 'upcoming';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'sort', except: 'starts_at')]
    public string $sortBy = 'starts_at';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /** @var list<string> */
    public array $statusOptions = [
        'pending_approval',
        'confirmed',
        'declined',
        'cancelled',
        'completed',
        'no_show',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view own bookings'), 403);
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedWhen(): void
    {
        if (! in_array($this->when, ['upcoming', 'past', 'all'], true)) {
            $this->when = 'upcoming';
        }

        if ($this->when === 'upcoming') {
            $this->sortBy = 'starts_at';
            $this->sortDirection = 'asc';
        } elseif ($this->when === 'past') {
            $this->sortBy = 'starts_at';
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['starts_at', 'status', 'session'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $column === 'starts_at' ? 'asc' : 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['q', 'status', 'role', 'dateFrom', 'dateTo']);
        $this->when = 'upcoming';
        $this->sortBy = 'starts_at';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function render(): View
    {
        $userId = (int) auth()->id();

        $bookings = $this->appointmentsQuery($userId)
            ->paginate(12);

        return view('livewire.booking.my-bookings-list', [
            'bookings' => $bookings,
            'resultCount' => $bookings->total(),
        ]);
    }

    private function appointmentsQuery(int $userId): Builder
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return Booking::query()
            ->with([
                'eventType:id,title,duration_minutes',
                'cfm:id,name,email',
                'trainee:id,name,email',
                'attendees.user:id,name,email',
            ])
            ->where(function (Builder $query) use ($userId): void {
                match ($this->role) {
                    'cfm' => $query->where('cfm_id', $userId),
                    'trainee' => $query->where('trainee_id', $userId),
                    'attendee' => $query->whereHas('attendees', fn (Builder $attendees) => $attendees->where('user_id', $userId)),
                    default => $query
                        ->where('cfm_id', $userId)
                        ->orWhere('trainee_id', $userId)
                        ->orWhereHas('attendees', fn (Builder $attendees) => $attendees->where('user_id', $userId)),
                };
            })
            ->when($this->status !== '' && in_array($this->status, $this->statusOptions, true), function (Builder $query): void {
                $query->where('status', $this->status);
            })
            ->when($this->when === 'upcoming', fn (Builder $query) => $query->where('starts_at', '>=', now()))
            ->when($this->when === 'past', fn (Builder $query) => $query->where('starts_at', '<', now()))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('starts_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('starts_at', '<=', $this->dateTo))
            ->when(trim($this->q) !== '', function (Builder $query): void {
                $term = '%'.trim($this->q).'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('location_details', 'like', $term)
                        ->orWhere('meeting_link', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('topics', 'like', $term)
                        ->orWhere('public_id', 'like', $term)
                        ->orWhereHas('eventType', fn (Builder $eventType) => $eventType->where('title', 'like', $term))
                        ->orWhereHas('cfm', fn (Builder $cfm) => $cfm->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('trainee', fn (Builder $trainee) => $trainee->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('attendees', fn (Builder $attendees) => $attendees->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->sortBy === 'status', fn (Builder $query) => $query->orderBy('status', $direction)->orderBy('starts_at', 'asc'))
            ->when($this->sortBy === 'session', function (Builder $query) use ($direction): void {
                $query->orderBy(
                    \App\Models\BookingEventType::query()
                        ->select('title')
                        ->whereColumn('booking_event_types.id', 'bookings.booking_event_type_id')
                        ->limit(1),
                    $direction,
                )->orderBy('starts_at', 'asc');
            })
            ->when(! in_array($this->sortBy, ['status', 'session'], true), function (Builder $query) use ($direction): void {
                $query->orderBy('starts_at', $direction);
            });
    }
}
