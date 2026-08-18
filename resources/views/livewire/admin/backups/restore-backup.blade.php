<div class="mx-auto max-w-7xl space-y-6">
    <x-admin.page-hero
        eyebrow="Backup operations"
        title="Restore backup"
    >
        <x-slot:actions>
            <a href="{{ route('admin.backups.restores') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Restore history</a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('restore_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('restore_status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="space-y-5 rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            @if (! $backupId)
                <div>
                    <label for="backupFile" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Upload backup ZIP</label>
                    <input id="backupFile" type="file" wire:model="backupFile" accept=".zip" class="mt-2 block w-full rounded-md border border-slate-300 text-sm shadow-sm file:mr-4 file:border-0 file:bg-gw-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white focus:border-gw-accent focus:ring-gw-accent">
                    @error('backupFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="button" wire:click="validateBackup" wire:loading.attr="disabled" class="mt-3 rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#132a4d] disabled:opacity-60">Validate backup</button>
                </div>
            @else
                <div class="rounded-xl border border-gw-accent/40 bg-gw-accent-soft p-4 text-sm text-gw-accent-dark">
                    Restoring from an existing backup history record.
                </div>
            @endif

            @if ($preview)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Backup preview</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs uppercase text-slate-500">Filename</dt><dd class="font-semibold text-gw-primary">{{ $preview['filename'] ?? 'Backup file' }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">Type</dt><dd class="font-semibold capitalize text-gw-primary">{{ str_replace('_', ' ', $preview['backup_type'] ?? 'unknown') }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">Created</dt><dd class="font-semibold text-gw-primary">{{ $preview['created_at'] ?? 'Unknown' }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">Tables</dt><dd class="font-semibold text-gw-primary">{{ count($preview['included_tables'] ?? []) }}</dd></div>
                    </dl>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Tables to restore</h2>
                        <span class="text-xs text-slate-500">{{ count($selectedTables) }} selected</span>
                    </div>
                    @error('selectedTables') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-3 grid max-h-80 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2">
                        @foreach (($preview['included_tables'] ?? []) as $table)
                            <label class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                <span class="truncate font-mono text-xs text-slate-700">{{ $table }}</span>
                                <span class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">{{ number_format(($preview['record_counts'][$table] ?? 0)) }}</span>
                                    <input type="checkbox" wire:model.live="selectedTables" value="{{ $table }}" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-red-800">Restore warning</h2>
                <p class="mt-2 text-sm text-red-700">Restoring can replace or merge production records. A pre-restore backup is created automatically, but this action should still be treated as high risk.</p>
            </section>

            <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Restore options</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Restore type</label>
                        <select wire:model="restoreType" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="selected">Selected tables</option>
                            <option value="structural">Structural data</option>
                            <option value="transactional">Transactional data</option>
                            <option value="combined">Structural + transactional</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Restore mode</label>
                        <select wire:model="restoreMode" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="skip_duplicates">Skip duplicate records</option>
                            <option value="merge">Merge with existing data</option>
                            <option value="replace">Replace existing data</option>
                        </select>
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Admin password</label>
                        <input id="password" type="password" wire:model="password" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="restore" wire:confirm="Queue this restore? A pre-restore backup will be created first." wire:loading.attr="disabled" class="w-full rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800 disabled:opacity-60">
                        Queue restore
                    </button>
                </div>
            </section>
        </aside>
    </div>
</div>
