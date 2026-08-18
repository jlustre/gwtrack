<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('communications.campaigns.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Campaign Center</a>
        <h1 class="mt-4 text-2xl font-semibold text-gw-primary">Create campaign</h1>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-2xl border border-gw-primary/10 bg-white p-6 shadow-sm">
        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Campaign name</label>
            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Type</label>
            <select wire:model="type" class="w-full rounded-lg border-slate-300 text-sm">
                @foreach ($campaignTypes as $code => $meta)
                    <option value="{{ $code }}">{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gw-primary">Starts</label>
                <input type="date" wire:model="starts_at" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gw-primary">Ends</label>
                <input type="date" wire:model="ends_at" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Description</label>
            <textarea wire:model="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Rules</label>
            <textarea wire:model="rules" rows="4" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-gw-primary">Prizes (one per line)</label>
            <textarea wire:model="prizes" rows="3" class="w-full rounded-lg border-slate-300 text-sm" placeholder="1st place: $500 gift card"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="publish_announcement" class="rounded border-slate-300 text-gw-accent">
            Publish campaign announcement to the hub
        </label>
        <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent">Create campaign</button>
    </form>
</div>
