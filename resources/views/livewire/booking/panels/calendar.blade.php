<section class="grid gap-3 rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm md:grid-cols-2 xl:grid-cols-3">
    @forelse ($bookings as $booking)
        <article wire:key="calendar-booking-{{ $booking->id }}" class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">{{ $booking->starts_at->format('M j, Y') }}</p>
            <h2 class="mt-1 font-semibold text-gw-primary">{{ $booking->eventType?->title ?? 'Mentor Session' }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $booking->starts_at->format('g:i A') }} - {{ $booking->ends_at->format('g:i A') }}</p>
            <p class="mt-2 text-xs font-semibold text-slate-500">{{ str($booking->status)->headline() }}</p>
        </article>
    @empty
        <div class="rounded-lg border border-dashed border-[#516070]/40 bg-[#F8FAFC] p-6 text-sm text-slate-500">No booking sessions on this calendar yet.</div>
    @endforelse
</section>
