<div class="mx-auto max-w-7xl space-y-6">
    <x-admin.page-hero
        eyebrow="Backup operations"
        title="Create backup"
    >
        <x-slot:actions>
            <a href="{{ route('admin.backups.history') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Backup history</a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('backup_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('backup_status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'structural' => 'Structural Data',
                    'transactional' => 'Transactional Data',
                    'combined' => 'Structural + Transactional',
                    'full' => 'Full Database',
                ] as $value => $label)
                    <label class="cursor-pointer rounded-xl border p-4 text-sm transition @if($backupType === $value) border-gw-accent bg-gw-accent-soft @else border-slate-200 bg-white hover:border-gw-accent/50 @endif">
                        <input type="radio" class="sr-only" wire:model.live="backupType" value="{{ $value }}">
                        <span class="font-semibold text-gw-primary">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Included tables</h2>
                    <span class="text-xs text-slate-500">{{ count($selectedTables) }} selected</span>
                </div>
                @error('selectedTables') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-3 grid max-h-[28rem] gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($availableTables as $table)
                        <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                            <input type="checkbox" wire:model.live="selectedTables" value="{{ $table }}" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                            <span class="truncate font-mono text-xs text-slate-700">{{ $table }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Options</h2>
                <div class="mt-4 space-y-3">
                    <label class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-medium text-slate-700">ZIP compression</span>
                        <input type="checkbox" wire:model="compressed" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                    </label>
                    <label class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-medium text-slate-700">Archive encryption</span>
                        <input type="checkbox" wire:model="encrypted" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent" disabled>
                    </label>
                    <p class="text-xs text-slate-500">Encryption controls are reserved for a key-managed rollout.</p>
                </div>
                <div class="mt-4">
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea id="notes" wire:model="notes" rows="4" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                    @error('notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="createBackup" wire:loading.attr="disabled" class="mt-5 w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#132a4d] disabled:opacity-60">
                    <span wire:loading.remove>Create backup</span>
                    <span wire:loading>Queueing...</span>
                </button>
            </section>
        </aside>
    </div>
</div>
