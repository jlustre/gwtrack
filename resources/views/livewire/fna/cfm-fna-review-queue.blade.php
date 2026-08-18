<div>
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['pending' => 'Awaiting Review', 'revision' => 'Revision Requested', 'approved' => 'Approved', 'all' => 'All'] as $value => $label)
            <button type="button" wire:click="$set('statusFilter', '{{ $value }}')"
                class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusFilter === $value ? 'bg-gw-primary text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Trainee</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Complete</th>
                        <th class="px-4 py-3">Missing</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php $missing = $completeness->missingSections($record); @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $record->owner?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $record->client_name }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $record->reference_code }}</td>
                            <td class="px-4 py-3">{{ $record->statusLabel() }}</td>
                            <td class="px-4 py-3">{{ $record->completeness_score }}%</td>
                            <td class="px-4 py-3 text-xs text-amber-700">{{ $missing ? implode(', ', array_slice($missing, 0, 2)).(count($missing) > 2 ? '…' : '') : '—' }}</td>
                            <td class="px-4 py-3">{{ $record->submitted_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3"><a href="{{ route('team.fna.show', $record) }}" class="text-gw-accent-dark hover:underline">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-600">No records in this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="border-t px-4 py-3">{{ $records->links() }}</div>
        @endif
    </div>
</div>
