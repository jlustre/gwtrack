<div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-gw-primary">{{ $title }}</h2>
    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-300 bg-white/85">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Prospect</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Stage</th>
                    <th class="px-4 py-3">Interest</th>
                    <th class="px-4 py-3">Next Follow-Up</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($rows as $prospect)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gw-primary">{{ $prospect->first_name }} {{ $prospect->last_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $prospect->email ?? $prospect->phone ?? 'Not set' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $prospect->stage?->name ?? 'No Stage' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ str($prospect->interest_level)->title() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $prospect->next_follow_up_at?->format('M j, g:i A') ?? 'Not scheduled' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No prospects yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
