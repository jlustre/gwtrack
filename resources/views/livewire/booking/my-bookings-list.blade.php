@php
    $statusStyles = [
        'pending_approval' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'confirmed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'declined' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'completed' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'no_show' => 'bg-orange-50 text-orange-800 ring-orange-200',
    ];

    $sortIndicator = function (string $column) use ($sortBy, $sortDirection): string {
        if ($sortBy !== $column) {
            return '';
        }

        return $sortDirection === 'asc' ? ' ↑' : ' ↓';
    };
@endphp

<div class="space-y-4" wire:key="my-appointments-table">
    @if (session('booking-status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ session('booking-status') }}</div>
    @endif
    <section class="rounded-xl border border-[#516070]/30 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Appointments</p>
                <h2 class="mt-1 text-lg font-semibold text-gw-primary">Your mentor sessions</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span wire:loading.remove wire:target="q,status,role,when,dateFrom,dateTo,sort,clearFilters,gotoPage,previousPage,nextPage">
                        {{ number_format($resultCount) }} {{ $resultCount === 1 ? 'appointment' : 'appointments' }}
                    </span>
                    <span wire:loading wire:target="q,status,role,when,dateFrom,dateTo,sort,clearFilters,gotoPage,previousPage,nextPage">Updating…</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('when', '{{ $value }}')"
                        @class([
                            'rounded-full px-3 py-1.5 text-xs font-semibold transition',
                            'bg-gw-primary text-white' => $when === $value,
                            'border border-gw-accent/40 bg-gw-accent-soft text-gw-primary hover:bg-gw-accent-muted' => $when !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <label class="block text-sm font-semibold text-gw-primary md:col-span-2 xl:col-span-2">
                Search
                <input
                    type="search"
                    wire:model.live.debounce.300ms="q"
                    placeholder="Session, person, topic, location…"
                    class="mt-1 w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
            </label>

            <label class="block text-sm font-semibold text-gw-primary">
                Status
                <select wire:model.live="status" class="mt-1 w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option }}">{{ str($option)->headline() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm font-semibold text-gw-primary">
                My role
                <select wire:model.live="role" class="mt-1 w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Any role</option>
                    <option value="cfm">As CFM</option>
                    <option value="trainee">As trainee</option>
                    <option value="attendee">As attendee</option>
                </select>
            </label>

            <label class="block text-sm font-semibold text-gw-primary">
                From
                <input type="date" wire:model.live="dateFrom" class="mt-1 w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            </label>

            <label class="block text-sm font-semibold text-gw-primary">
                To
                <input type="date" wire:model.live="dateTo" class="mt-1 w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            </label>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">Filters update instantly without reloading the page.</p>
            <button
                type="button"
                wire:click="clearFilters"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Clear filters
            </button>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-[#516070]/30 bg-white shadow-sm" x-data="{ expanded: null }">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gw-primary text-left text-xs font-semibold uppercase tracking-wide text-gw-accent">
                    <tr>
                        <th class="w-10 px-3 py-3"></th>
                        <th class="px-3 py-3">
                            <button type="button" wire:click="sort('session')" class="inline-flex items-center gap-1 hover:text-white">
                                Session{{ $sortIndicator('session') }}
                            </button>
                        </th>
                        <th class="px-3 py-3">Participants</th>
                        <th class="px-3 py-3">
                            <button type="button" wire:click="sort('starts_at')" class="inline-flex items-center gap-1 hover:text-white">
                                When{{ $sortIndicator('starts_at') }}
                            </button>
                        </th>
                        <th class="px-3 py-3">Location</th>
                        <th class="px-3 py-3">
                            <button type="button" wire:click="sort('status')" class="inline-flex items-center gap-1 hover:text-white">
                                Status{{ $sortIndicator('status') }}
                            </button>
                        </th>
                        <th class="px-3 py-3">Your role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $booking)
                        @php
                            $userId = auth()->id();
                            $yourRole = match (true) {
                                $booking->cfm_id === $userId => 'CFM',
                                $booking->trainee_id === $userId => 'Trainee',
                                $booking->attendees->contains(fn ($attendee) => $attendee->user_id === $userId) => 'Attendee',
                                default => 'Participant',
                            };
                            $rowKey = $booking->id;
                        @endphp
                        <tr
                            wire:key="appointment-{{ $booking->id }}"
                            class="align-top hover:bg-gw-accent-soft/50"
                        >
                            <td class="px-3 py-3">
                                <button
                                    type="button"
                                    class="gw-icon-btn-close h-8 w-8"
                                    x-on:click="expanded = expanded === @js($rowKey) ? null : @js($rowKey)"
                                    x-bind:aria-expanded="expanded === @js($rowKey)"
                                    aria-label="Toggle appointment details"
                                >
                                    <svg class="h-4 w-4 transition" x-bind:class="expanded === @js($rowKey) ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-gw-primary">{{ $booking->eventType?->title ?? 'Mentor Session' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $booking->eventType?->duration_minutes ? $booking->eventType->duration_minutes.' min' : '—' }}
                                    · {{ strtoupper(substr((string) $booking->public_id, -8)) }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-gw-primary">
                                    <span class="text-xs font-bold uppercase text-slate-400">CFM</span>
                                    <div class="font-medium">{{ $booking->cfm?->name ?? '—' }}</div>
                                </div>
                                <div class="mt-2 text-gw-primary">
                                    <span class="text-xs font-bold uppercase text-slate-400">Trainee</span>
                                    <div class="font-medium">{{ $booking->trainee?->name ?? 'External invitee' }}</div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-700">
                                <div class="font-semibold text-gw-primary">{{ $booking->starts_at?->format('D, M j, Y') }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $booking->starts_at?->format('g:i A') }}
                                    @if ($booking->ends_at)
                                        – {{ $booking->ends_at->format('g:i A') }}
                                    @endif
                                </div>
                                <div class="mt-0.5 text-[0.65rem] font-medium uppercase tracking-wide text-slate-400">{{ $booking->timezone }}</div>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                <div class="font-medium text-gw-primary">{{ str($booking->location_type)->headline() }}</div>
                                <div class="mt-0.5 line-clamp-2 text-xs">{{ $booking->location_details ?: '—' }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                    $statusStyles[$booking->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200',
                                ])>
                                    {{ str($booking->status)->headline() }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full bg-gw-accent-soft px-2.5 py-1 text-xs font-semibold text-gw-accent-dark ring-1 ring-inset ring-gw-accent/40">
                                    {{ $yourRole }}
                                </span>
                            </td>
                        </tr>
                        <tr wire:key="appointment-details-{{ $booking->id }}" x-show="expanded === @js($rowKey)" x-cloak>
                            <td colspan="7" class="bg-[#F8FAFC] px-4 py-4">
                                <div class="grid gap-4 lg:grid-cols-3">
                                    <div class="rounded-lg border border-[#516070]/15 bg-white p-3">
                                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Session details</p>
                                        <dl class="mt-2 space-y-2 text-sm text-slate-600">
                                            <div>
                                                <dt class="font-semibold text-gw-primary">Topics</dt>
                                                <dd class="mt-0.5 whitespace-pre-line">{{ $booking->topics ?: 'No topics listed.' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold text-gw-primary">Reason</dt>
                                                <dd class="mt-0.5 whitespace-pre-line">{{ $booking->reason ?: 'No reason provided.' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="rounded-lg border border-[#516070]/15 bg-white p-3">
                                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Meeting</p>
                                        <dl class="mt-2 space-y-2 text-sm text-slate-600">
                                            <div>
                                                <dt class="font-semibold text-gw-primary">Location</dt>
                                                <dd class="mt-0.5">{{ $booking->location_details ?: str($booking->location_type)->headline() }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold text-gw-primary">Meeting link</dt>
                                                <dd class="mt-0.5">
                                                    @if ($booking->meeting_link)
                                                        <a href="{{ $booking->meeting_link }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-gw-accent-dark underline break-all">
                                                            {{ $booking->meeting_link }}
                                                        </a>
                                                    @else
                                                        Not set
                                                    @endif
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="rounded-lg border border-[#516070]/15 bg-white p-3">
                                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Attendees</p>
                                        <div class="mt-2 max-h-40 space-y-2 overflow-y-auto">
                                            @forelse ($booking->attendees as $attendee)
                                                <div class="rounded-md bg-[#F8FAFC] px-3 py-2">
                                                    <div class="text-sm font-semibold text-gw-primary">{{ $attendee->name ?: ($attendee->user?->name ?? $attendee->email ?? 'Guest') }}</div>
                                                    <div class="text-xs text-slate-500">
                                                        {{ str($attendee->attendee_type)->headline() }}
                                                        · {{ str($attendee->rsvp_status)->headline() }}
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-sm text-slate-500">No additional attendees.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                @if (in_array($booking->status, ['confirmed', 'pending_approval'], true)
                                    && $booking->starts_at->isFuture()
                                    && ($booking->cfm_id === auth()->id() || $booking->trainee_id === auth()->id()))
                                    <div class="mt-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-3 sm:flex-row sm:items-end">
                                        @if ($booking->status === 'confirmed' && $canReschedule)
                                            <form wire:submit="requestReschedule('{{ $booking->id }}')" class="grid flex-1 gap-3 sm:grid-cols-3 sm:items-end">
                                                <div>
                                                    <label for="reschedule-at-{{ $booking->id }}" class="block text-xs font-semibold text-slate-600">Request a new time ({{ $booking->timezone }})</label>
                                                    <input id="reschedule-at-{{ $booking->id }}" type="datetime-local" wire:model="rescheduleAt.{{ $booking->id }}" min="{{ now($booking->timezone)->format('Y-m-d\TH:i') }}" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                                    @error('rescheduleAt.'.$booking->id) <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label for="reschedule-reason-{{ $booking->id }}" class="block text-xs font-semibold text-slate-600">Reason (optional)</label>
                                                    <input id="reschedule-reason-{{ $booking->id }}" wire:model="rescheduleReason.{{ $booking->id }}" maxlength="1000" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                                </div>
                                                <button class="h-10 rounded-md border border-gw-primary px-3 text-xs font-semibold text-gw-primary">Request reschedule</button>
                                            </form>
                                        @endif
                                        @if ($canCancel)
                                            <button type="button" wire:click="cancelBooking('{{ $booking->id }}')" wire:confirm="Cancel this booking?" class="h-10 rounded-md border border-rose-300 px-3 text-xs font-semibold text-rose-700">Cancel booking</button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <p class="text-sm font-semibold text-gw-primary">No appointments match your filters</p>
                                <p class="mt-1 text-sm text-slate-500">Try clearing filters or switching to All appointments.</p>
                                <button type="button" wire:click="clearFilters" class="mt-4 rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">
                                    Clear filters
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bookings->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $bookings->links() }}
            </div>
        @endif
    </section>
</div>
