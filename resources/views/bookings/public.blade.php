<x-guest-layout>
    <div class="min-h-screen bg-[#07101F] px-4 py-8 text-white">
        <div class="mx-auto grid max-w-6xl overflow-hidden rounded-2xl border border-gw-accent/50 bg-white text-gw-primary shadow-2xl lg:grid-cols-[24rem_minmax(0,1fr)]">
            <aside class="bg-gw-primary p-6 text-white">
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Your Greatway to Growth</p>
                <h1 class="mt-4 text-2xl font-semibold">{{ $mentor?->name ?? 'CFM Booking Page' }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-200">Certified Field Mentor scheduling for apprentices, trainees, and supported prospect sessions.</p>
                <div class="mt-6 rounded-lg border border-gw-accent/40 bg-white/10 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Booking Flow</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-200">
                        <li>1. Choose a mentor session type.</li>
                        <li>2. Pick an available time slot.</li>
                        <li>3. Answer the required booking questions.</li>
                        <li>4. Confirm or wait for CFM approval.</li>
                    </ul>
                </div>
            </aside>

            <main class="p-6">
                <p class="mb-4 text-xs font-bold uppercase tracking-wide text-gw-accent">Book a Session</p>
                @livewire('booking.public-booking-page', [
                    'mentorId' => $mentor?->id,
                    'eventTypeSlug' => $eventTypeSlug,
                    'inviteToken' => $inviteLink?->token,
                ])
            </main>
        </div>
    </div>
</x-guest-layout>
