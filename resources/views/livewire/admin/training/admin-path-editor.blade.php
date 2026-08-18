<div class="space-y-6">
    @if (session('admin_training_status') === 'path-saved')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Learning path saved.</div>
    @endif

    <x-admin.page-hero
        eyebrow="Admin · Academy Builder"
        :title="$path->name"
        :description="$path->code . ' · ' . count($moduleRows) . ' courses'"
        class="rounded-xl"
    >
        <x-slot:actions>
            <a href="{{ route('admin.training.paths.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">&larr; All paths</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Path settings</h2>
            <form wire:submit="savePath" class="mt-4 space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Name</label>
                    <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Code</label>
                    <input type="text" wire:model="code" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Description</label>
                    <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Audience</label>
                        <select wire:model="audience" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            <option value="">General</option>
                            @foreach ($audiences as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Sort order</label>
                        <input type="number" min="0" wire:model="sortOrder" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-gw-accent"> Active path</label>
                <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save path</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Path courses</h2>
            <div class="mt-4 flex flex-wrap items-end gap-2">
                <div class="min-w-[12rem] flex-1">
                    <label class="text-sm font-semibold text-gw-primary">Add published course</label>
                    <select wire:model="attachModuleId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Select course</option>
                        @foreach ($availableModules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="addModuleRow" class="inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-slate-50">Add</button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($moduleRows as $index => $row)
                    @php $module = $moduleLookup->get($row['module_id']); @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $module?->title ?? 'Course #'.$row['module_id'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $module?->slug }}</p>
                            </div>
                            <button type="button" wire:click="removeModuleRow({{ $index }})" class="text-xs font-semibold text-red-700 underline">Remove</button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">Sort order</label>
                                <input type="number" min="0" wire:model.live="moduleRows.{{ $index }}.sort_order" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model.live="moduleRows.{{ $index }}.is_required" class="rounded border-slate-300 text-gw-accent">
                                    Required
                                </label>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Add published courses to this learning path.</p>
                @endforelse
            </div>
            <p class="mt-4 text-xs text-slate-500">Save path settings to persist course order and requirements.</p>
        </div>
    </div>
</div>
