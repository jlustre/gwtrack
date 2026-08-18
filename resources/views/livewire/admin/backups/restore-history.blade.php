<div class="mx-auto max-w-7xl space-y-6" wire:poll.10s>
    <x-admin.page-hero
        eyebrow="Backup operations"
        title="Restore history"
    >
        <x-slot:actions>
            <a href="{{ route('admin.backups.restore') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">Restore backup</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
        <div class="max-w-xs">
            <select wire:model.live="status" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Restore</th>
                        <th class="px-3 py-3">Mode</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Restored by</th>
                        <th class="px-3 py-3">Tables</th>
                        <th class="px-3 py-3">Completed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($restores as $restore)
                        <tr class="align-top">
                            <td class="px-3 py-3">
                                <p class="font-semibold text-gw-primary">Restore #{{ $restore->id }}</p>
                                <p class="text-xs text-slate-500">{{ $restore->started_at?->toDayDateTimeString() ?? $restore->created_at?->toDayDateTimeString() }}</p>
                                @if ($restore->error_message)
                                    <p class="mt-1 max-w-md text-xs text-red-600">{{ $restore->error_message }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 capitalize">{{ str_replace('_', ' ', $restore->restore_mode) }}</td>
                            <td class="px-3 py-3">{{ ucfirst($restore->status) }}</td>
                            <td class="px-3 py-3">{{ $restore->restorer?->name ?? 'System' }}</td>
                            <td class="px-3 py-3">
                                <div class="max-w-md text-xs text-slate-600">{{ implode(', ', $restore->restored_tables ?? []) }}</div>
                                @if ($restore->items->isNotEmpty())
                                    <div class="mt-2 space-y-1">
                                        @foreach ($restore->items as $item)
                                            <p class="text-xs text-slate-500">{{ $item->table_name }}: {{ number_format($item->records_restored) }} restored, {{ number_format($item->records_skipped) }} skipped</p>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $restore->completed_at?->toDayDateTimeString() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">No restore activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $restores->links() }}</div>
    </section>
</div>
