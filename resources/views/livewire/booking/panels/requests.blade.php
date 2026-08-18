<section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
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
                        <td class="px-3 py-2 text-slate-500">Approve / Decline scaffold</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No booking requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</section>
