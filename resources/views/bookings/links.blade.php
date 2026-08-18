<x-app-layout>
    <x-bookings.partials.shell title="Booking Links" subtitle="Personal, event-specific, apprentice-only, invite-only, one-time, and expiring booking links.">
        <section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Type</th><th class="px-3 py-2">Event</th><th class="px-3 py-2">Uses</th><th class="px-3 py-2">Link</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($links as $link)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-gw-primary">{{ $link->name }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ str($link->link_type)->headline() }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $link->eventType?->title ?? 'Personal page' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $link->uses_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}</td>
                                <td class="px-3 py-2"><a class="font-semibold text-gw-accent-dark" href="{{ route('bookings.invite', $link->token) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No booking links yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-bookings.partials.shell>
</x-app-layout>
