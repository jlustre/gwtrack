<x-app-layout>
    <x-bookings.partials.shell
        title="Mentor Booking Dashboard"
        subtitle="Manage CFM availability, booking links, pending requests, and mentor session outcomes connected to Field Apprenticeship."
    >
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Upcoming', 'value' => $upcomingBookings->count(), 'theme' => 'cyan', 'subtitle' => 'Scheduled mentor sessions'],
                    ['label' => 'Pending Requests', 'value' => $pendingRequests->count(), 'theme' => 'amber', 'subtitle' => 'Awaiting approval'],
                    ['label' => 'Event Types', 'value' => $eventTypes->count(), 'theme' => 'navy', 'subtitle' => 'Bookable session types'],
                    ['label' => 'Available Slots', 'value' => $availabilitySchedules, 'theme' => 'gold', 'subtitle' => 'Availability schedules configured'],
                ] as $card)
                    <x-tracker-stat-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :subtitle="$card['subtitle']"
                        :theme="$card['theme']"
                    />
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gw-primary">Upcoming Mentor Sessions</h2>
                    <a href="{{ route('bookings.my') }}" class="text-sm font-semibold text-gw-accent-dark">View all</a>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                            <tr><th class="px-3 py-2">Session</th><th class="px-3 py-2">When</th><th class="px-3 py-2">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($upcomingBookings as $booking)
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-gw-primary">{{ $booking->eventType?->title ?? 'Mentor Session' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $booking->starts_at->format('M j, g:i A') }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ str($booking->status)->headline() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">No upcoming booking sessions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gw-primary">Pending Requests</h2>
                    <a href="{{ route('bookings.requests') }}" class="text-sm font-semibold text-gw-accent-dark">Review</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($pendingRequests as $booking)
                        <div class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-3 py-2">
                            <div class="font-semibold text-gw-primary">{{ $booking->eventType?->title ?? 'Booking Request' }}</div>
                            <div class="text-xs text-slate-600">{{ $booking->starts_at->format('M j, g:i A') }} with {{ $booking->trainee?->name ?? 'External invitee' }}</div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-[#516070]/40 bg-[#F8FAFC] px-4 py-6 text-sm text-slate-500">No approval requests waiting.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </x-bookings.partials.shell>
</x-app-layout>
