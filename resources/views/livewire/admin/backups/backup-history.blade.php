<div class="mx-auto max-w-7xl space-y-6" wire:poll.10s>
    <x-admin.page-hero
        eyebrow="Backup operations"
        title="Backup history"
    >
        <x-slot:actions>
            <a href="{{ route('admin.backups.create') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">Create backup</a>
            <a href="{{ route('admin.backups.restore') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Restore</a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('backup_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('backup_status') }}</div>
    @endif

    <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search filename or creator" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            <select wire:model.live="type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All types</option>
                <option value="structural">Structural</option>
                <option value="transactional">Transactional</option>
                <option value="combined">Combined</option>
                <option value="full">Full</option>
                <option value="pre_restore">Pre-restore</option>
            </select>
            <select wire:model.live="status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
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
                        <th class="px-3 py-3">Backup</th>
                        <th class="px-3 py-3">Type</th>
                        <th class="px-3 py-3">Size</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Created by</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-gw-primary">{{ $backup->filename }}</p>
                                <p class="text-xs text-slate-500">{{ $backup->created_at?->toDayDateTimeString() }}</p>
                                @if ($backup->error_message)
                                    <p class="mt-1 max-w-md text-xs text-red-600">{{ $backup->error_message }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 capitalize">{{ str_replace('_', ' ', $backup->backup_type) }}</td>
                            <td class="px-3 py-3">{{ number_format($backup->file_size / 1048576, 2) }} MB</td>
                            <td class="px-3 py-3">{{ ucfirst($backup->status) }}</td>
                            <td class="px-3 py-3">{{ $backup->creator?->name ?? 'System' }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" wire:click="showMetadata({{ $backup->id }})" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Metadata</button>
                                    @if ($backup->status === 'completed')
                                        <a href="{{ route('admin.backups.download', $backup) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Download</a>
                                        <a href="{{ route('admin.backups.restore', ['backup' => $backup->id]) }}" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent/20">Restore</a>
                                    @endif
                                    @can('delete backups')
                                        <button type="button" wire:click="deleteBackup({{ $backup->id }})" wire:confirm="Delete this backup file and history record?" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">No backups found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $backups->links() }}</div>
    </section>

    @if ($metadataBackup)
        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Metadata: {{ $metadataBackup->filename }}</h2>
                <button type="button" wire:click="$set('metadataBackupId', null)" class="text-sm font-semibold text-slate-500 hover:text-gw-primary">Close</button>
            </div>
            <pre class="mt-4 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($metadataBackup->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    @endif
</div>
