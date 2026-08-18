<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('communications.recognition') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Recognition Center</a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-gw-accent">Post recognition</p>
        <h1 class="text-2xl font-semibold text-gw-primary">Celebrate a teammate</h1>
        <p class="mt-2 text-sm text-slate-600">Use a template, select the honoree, and optionally award a badge when published.</p>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-2xl border border-gw-primary/10 bg-white p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gw-primary">Recognition type</label>
                <select wire:model.live="template" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($templates as $item)
                        <option value="{{ $item['code'] }}">{{ $item['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gw-primary">Honoree</label>
                <select wire:model.live="honoree_user_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select associate...</option>
                    @foreach ($honorees as $honoree)
                        <option value="{{ $honoree->id }}">{{ $honoree->name }}</option>
                    @endforeach
                </select>
                @error('honoree_user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Badge to award</label>
            <select wire:model="badge_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">No badge</option>
                @foreach ($badges as $badge)
                    <option value="{{ $badge->id }}">{{ $badge->icon }} {{ $badge->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Headline</label>
            <input type="text" wire:model="title" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Summary</label>
            <input type="text" wire:model="summary" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent">
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Full message</label>
            <textarea wire:model="body" rows="8" class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
            @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-4">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="is_featured" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Feature on recognition wall
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="publish_now" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Publish immediately
            </label>
        </div>

        <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]">
            Save recognition post
        </button>
    </form>
</div>
