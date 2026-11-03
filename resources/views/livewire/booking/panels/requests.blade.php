<section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
    @if ($rescheduleRequests->isNotEmpty())
        <div class="mb-5 space-y-3">
            <h2 class="text-lg font-semibold text-gw-primary">Reschedule requests</h2>
            @foreach ($rescheduleRequests as $reschedule)
                <article wire:key="reschedule-request-{{ $reschedule->id }}" class="flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-700">
                        <p class="font-semibold">{{ $reschedule->booking->eventType?->title ?? 'Mentor session' }} · {{ $reschedule->booking->trainee?->name ?? 'External invitee' }}</p>
                        <p>Requested by {{ $reschedule->requestedBy?->name ?? 'Participant' }}: {{ $reschedule->old_starts_at->timezone($reschedule->booking->timezone)->format('M j, g:i A') }} → {{ $reschedule->new_starts_at->timezone($reschedule->booking->timezone)->format('M j, g:i A') }}</p>
                        @if ($reschedule->reason)<p class="mt-1 text-xs">{{ $reschedule->reason }}</p>@endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="resolveReschedule({{ $reschedule->id }}, true)" class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Approve</button>
                        <button type="button" wire:click="resolveReschedule({{ $reschedule->id }}, false)" class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700">Decline</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                <tr><th class="px-3 py-2">Session</th><th class="px-3 py-2">Trainee</th><th class="px-3 py-2">When</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($bookings as $booking)
                    <tr wire:key="request-{{ $booking->id }}">
                        <td class="px-3 py-2 font-semibold text-gw-primary">{{ $booking->eventType?->title ?? 'Mentor Session' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $booking->trainee?->name ?? 'External invitee' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $booking->starts_at->format('M j, g:i A') }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ str($booking->status)->headline() }}</td>
                        <td class="px-3 py-2">
                            @if ($booking->status === 'pending_approval')
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="approveBooking({{ $booking->id }})" class="rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white">Approve</button>
                                    <button type="button" wire:click="declineBooking({{ $booking->id }})" class="rounded-md border border-rose-300 px-2.5 py-1.5 text-xs font-semibold text-rose-700">Decline</button>
                                </div>
                            @elseif ($booking->status === 'confirmed')
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="cancelBooking({{ $booking->id }})" wire:confirm="Cancel this booking?" class="rounded-md border border-rose-300 px-2.5 py-1.5 text-xs font-semibold text-rose-700">Cancel</button>
                                    @if ($booking->ends_at->isPast())
                                        <button type="button" wire:click="completeBooking({{ $booking->id }})" class="rounded-md bg-sky-700 px-2.5 py-1.5 text-xs font-semibold text-white">Complete</button>
                                        <button type="button" wire:click="markBookingNoShow({{ $booking->id }})" wire:confirm="Mark this trainee as a no-show?" class="rounded-md border border-orange-300 px-2.5 py-1.5 text-xs font-semibold text-orange-800">No-show</button>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-500">No actions</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No booking requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</section>
