<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('communications.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Back to Communication Hub</a>
        <h1 class="mt-2 text-2xl font-semibold text-gw-primary">Create announcement</h1>
        <p class="mt-2 text-sm text-slate-600">Publish an update to your selected audience. Notifications are sent automatically when you publish.</p>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-2xl border border-gw-primary/10 bg-white p-6 shadow-sm">
        <div>
            <label for="category_id" class="block text-sm font-semibold text-gw-primary">Category</label>
            <select id="category_id" wire:model="category_id" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">Select category…</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="title" class="block text-sm font-semibold text-gw-primary">Headline</label>
            <input id="title" type="text" wire:model="title" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">AI draft assistant</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input type="text" wire:model="ai_topic" placeholder="Topic for suggested draft…" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <button type="button" wire:click="suggestDraft" class="shrink-0 rounded-md border border-gw-primary/15 bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-white/80">
                    Suggest draft
                </button>
            </div>
            @error('ai_topic') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="summary" class="block text-sm font-semibold text-gw-primary">Summary</label>
            <textarea id="summary" wire:model="summary" rows="2" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
            @error('summary') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="body" class="block text-sm font-semibold text-gw-primary">Full content</label>
            <textarea id="body" wire:model="body" rows="10" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
            @error('body') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="priority" class="block text-sm font-semibold text-gw-primary">Priority</label>
                <select id="priority" wire:model="priority" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($priorities as $code => $meta)
                        <option value="{{ $code }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="audience_type" class="block text-sm font-semibold text-gw-primary">Audience</label>
                <select id="audience_type" wire:model="audience_type" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($audienceTypes as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="requires_acknowledgement" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Require acknowledgement
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="is_pinned" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Pin to top of feed
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="publish_now" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Publish immediately and notify audience
            </label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('communications.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]">
                Save announcement
            </button>
        </div>
    </form>
</div>
