<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin · Academy Builder"
        title="Learning Path Builder"
        description="Create structured development journeys from published courses."
        class="rounded-xl"
    >
        <x-slot:actions>
            <a href="{{ route('admin.training.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">&larr; Training Studio</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-gw-primary">Learning paths</h2>
            <button type="button" wire:click="$toggle('showCreate')" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                {{ $showCreate ? 'Close form' : 'New path' }}
            </button>
        </div>

        @if ($showCreate)
            <form wire:submit="createPath" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Name</label>
                    <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Code (optional)</label>
                    <input type="text" wire:model="code" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="auto-generated if blank">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Audience</label>
                    <select wire:model="audience" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($audiences as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-semibold text-gw-primary">Description</label>
                    <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="lg:col-span-2">
                    <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Create path</button>
                </div>
            </form>
        @endif

        <div class="mt-5 space-y-3">
            @forelse ($paths as $path)
                <a href="{{ route('admin.training.paths.show', $path) }}" class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3 transition hover:border-gw-accent/40 hover:bg-gw-accent-soft">
                    <div>
                        <p class="font-semibold text-gw-primary">{{ $path->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $path->code }} · {{ $path->modules_count }} courses</p>
                    </div>
                    <span @class([
                        'rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase',
                        'bg-emerald-100 text-emerald-800' => $path->is_active,
                        'bg-slate-200 text-slate-600' => ! $path->is_active,
                    ])>{{ $path->is_active ? 'Active' : 'Inactive' }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-600">No learning paths yet.</p>
            @endforelse
        </div>
    </div>
</div>
