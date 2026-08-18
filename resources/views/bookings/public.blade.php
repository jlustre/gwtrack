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
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Book a Session</p>
                        <h2 class="mt-1 text-xl font-semibold">Available Event Types</h2>
                    </div>
                    <select class="rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                        <option>America/Vancouver</option>
                        <option>America/Toronto</option>
                        <option>America/New_York</option>
                        <option>America/Chicago</option>
                        <option>America/Los_Angeles</option>
                    </select>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @forelse ($eventTypes as $type)
                        <article class="rounded-lg border border-[#516070] bg-gw-accent-soft p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gw-primary">{{ $type->title }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $type->duration_minutes }} minutes · {{ str($type->location_type)->headline() }}</p>
                                </div>
                                <span class="h-3 w-3 rounded-full" style="background-color: {{ $type->color }}"></span>
                            </div>
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $type->description }}</p>
                            <div class="mt-4 rounded-md border border-dashed border-gw-accent/60 bg-white px-3 py-4 text-sm text-slate-500">
                                Date picker, time slots, booking questions, and confirmation screen will be activated in the Livewire implementation phase.
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-[#516070]/40 bg-[#F8FAFC] p-6 text-sm text-slate-500">This booking page is not available yet.</div>
                    @endforelse
                </div>
            </main>
        </div>
    </div>
</x-guest-layout>
