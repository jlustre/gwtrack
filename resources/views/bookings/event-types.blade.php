<x-app-layout>
    <x-bookings.partials.shell title="Booking Event Types" subtitle="Reusable mentor session templates with duration, buffers, booking windows, approval rules, and custom questions.">
        <section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Event Type</th><th class="px-3 py-2">Duration</th><th class="px-3 py-2">Approval</th><th class="px-3 py-2">Links</th><th class="px-3 py-2">Questions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($eventTypes as $type)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gw-primary">{{ $type->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $type->slug }}</div>
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $type->duration_minutes }} min</td>
                                <td class="px-3 py-2 text-slate-600">{{ $type->approval_required ? 'Required' : 'Auto-confirm' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $type->links_count }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $type->questions_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No booking event types yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-bookings.partials.shell>
</x-app-layout>
