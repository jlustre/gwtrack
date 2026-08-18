<x-app-layout>
    <x-bookings.partials.shell title="My Availability" subtitle="Calendly-style weekly windows, overrides, blackout dates, and timezone-aware availability for CFM sessions.">
        <section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gw-primary">Availability Schedules</h2>
                <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-3 py-1 text-xs font-bold text-gw-primary">{{ $schedules->count() }}</span>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Schedule</th><th class="px-3 py-2">Timezone</th><th class="px-3 py-2">Rules</th><th class="px-3 py-2">Overrides</th><th class="px-3 py-2">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($schedules as $schedule)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-gw-primary">{{ $schedule->name }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $schedule->timezone }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $schedule->rules->count() }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $schedule->overrides->count() }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $schedule->is_active ? 'Active' : 'Inactive' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No availability schedules created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-bookings.partials.shell>
</x-app-layout>
