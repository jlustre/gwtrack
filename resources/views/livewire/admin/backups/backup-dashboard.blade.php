<div class="mx-auto max-w-7xl space-y-6" wire:poll.10s>
    <x-admin.page-hero
        eyebrow="Administration"
        title="Backup operations"
    >
        <x-slot:actions>
            <a href="{{ route('admin.backups.create') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">Create backup</a>
            <a href="{{ route('admin.backups.restore') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Restore</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <x-tracker-stat-card label="Total backups" :value="number_format($metrics['total_backups'])" theme="navy" />
        <x-tracker-stat-card label="Last successful" :value="$metrics['last_successful_backup']?->completed_at?->diffForHumans() ?? 'None'" theme="cyan" />
        <x-tracker-stat-card label="Failed backups" :value="number_format($metrics['failed_backups'])" theme="red" />
        <x-tracker-stat-card label="Storage used" :value="number_format($metrics['storage_used'] / 1048576, 1).' MB'" theme="gold" />
        <x-tracker-stat-card label="Next scheduled" :value="$metrics['next_scheduled_backup']?->next_run_at?->diffForHumans() ?? 'Not set'" theme="navy" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Recent backups</h2>
                <a href="{{ route('admin.backups.history') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">History</a>
            </div>
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentBackups as $backup)
                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-gw-primary">{{ $backup->filename }}</p>
                                    <p class="text-xs text-slate-500">{{ str_replace('_', ' ', $backup->backup_type) }} by {{ $backup->creator?->name ?? 'System' }}</p>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold @class([
                                        'bg-emerald-50 text-emerald-700' => $backup->status === 'completed',
                                        'bg-red-50 text-red-700' => $backup->status === 'failed',
                                        'bg-amber-50 text-amber-700' => in_array($backup->status, ['pending', 'processing'], true),
                                    ])">{{ ucfirst($backup->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-3 py-6 text-center text-slate-500">No backups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Recent restores</h2>
                <a href="{{ route('admin.backups.restores') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">Restore history</a>
            </div>
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentRestores as $restore)
                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-gw-primary">Restore #{{ $restore->id }}</p>
                                    <p class="text-xs text-slate-500">{{ str_replace('_', ' ', $restore->restore_type) }} by {{ $restore->restorer?->name ?? 'System' }}</p>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold @class([
                                        'bg-emerald-50 text-emerald-700' => $restore->status === 'completed',
                                        'bg-red-50 text-red-700' => $restore->status === 'failed',
                                        'bg-amber-50 text-amber-700' => in_array($restore->status, ['pending', 'processing'], true),
                                    ])">{{ ucfirst($restore->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-3 py-6 text-center text-slate-500">No restores yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
