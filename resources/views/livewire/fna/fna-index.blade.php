<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="fna-search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input id="fna-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Client or reference…" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label for="fna-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select id="fna-status" wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fna-dime" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">DIME Completed</label>
                <select id="fna-dime" wire:model.live="dimeCompleted" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Any</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="clearFilters" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear filters</button>
            </div>
            <div>
                <label for="fna-created-from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Created from</label>
                <input id="fna-created-from" type="date" wire:model.live="createdFrom" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label for="fna-created-to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Created to</label>
                <input id="fna-created-to" type="date" wire:model.live="createdTo" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label for="fna-gap-min" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Gap min ($)</label>
                <input id="fna-gap-min" type="number" min="0" wire:model.live.debounce.300ms="gapMin" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label for="fna-gap-max" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Gap max ($)</label>
                <input id="fna-gap-max" type="number" min="0" wire:model.live.debounce.300ms="gapMax" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">DIME</th>
                        <th class="px-4 py-3">Gap</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gw-primary">{{ $record->reference_code }}</td>
                            <td class="px-4 py-3">{{ $record->client_name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border border-slate-300 bg-slate-50 px-2 py-0.5 text-xs font-semibold">{{ $record->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $record->dime_completed ? '✓' : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $record->protection_gap !== null ? '$'.number_format((float) $record->protection_gap, 0) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $record->updated_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('team.fna.show', $record) }}" class="text-gw-accent-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-600">No FNA records match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </div>
</div>
