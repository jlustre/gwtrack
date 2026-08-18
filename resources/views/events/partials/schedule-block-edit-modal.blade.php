<div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-lg rounded-xl border border-gw-accent/50 bg-white shadow-xl" x-on:click.outside="editOpen = false">
        <form method="POST" action="{{ route('calendar.schedule-blocks.update', $block) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Weekly Block</p>
                        <h3 class="text-lg font-semibold text-gw-primary">View / Edit Details</h3>
                    </div>
                    <button type="button" x-on:click="editOpen = false" class="gw-icon-btn-close" aria-label="Close block details">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                    </button>
                </div>
                <p class="mt-2 text-sm text-slate-500">{{ $block->displayLabel() }} · {{ $block->weeklyRangeLabel($weekdayLabels) }}</p>
            </div>
            <div class="space-y-4 px-5 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="edit-block-type-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">Type</label>
                        <select id="edit-block-type-{{ $block->id }}" name="block_type" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($blockTypes as $key => $type)
                                <option value="{{ $key }}" @selected($block->block_type === $key)>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit-block-label-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">Label</label>
                        <input id="edit-block-label-{{ $block->id }}" name="label" value="{{ $block->label }}" placeholder="e.g. Day job, Gym" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="edit-block-weekday-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">From day</label>
                        <select id="edit-block-weekday-{{ $block->id }}" name="weekday" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($weekdayLabels as $value => $label)
                                <option value="{{ $value }}" @selected((int) $block->weekday === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit-block-starts-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">From time</label>
                        <input id="edit-block-starts-{{ $block->id }}" type="time" name="starts_at" value="{{ substr((string) $block->starts_at, 0, 5) }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="edit-block-end-weekday-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">To day</label>
                        <select id="edit-block-end-weekday-{{ $block->id }}" name="end_weekday" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Same day</option>
                            @foreach ($weekdayLabels as $value => $label)
                                <option value="{{ $value }}" @selected($block->end_weekday && (int) $block->end_weekday === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit-block-ends-{{ $block->id }}" class="block text-xs font-semibold text-gw-primary">To time</label>
                        <input id="edit-block-ends-{{ $block->id }}" type="time" name="ends_at" value="{{ substr((string) $block->ends_at, 0, 5) }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gw-primary">
                    <input type="checkbox" name="is_shared" value="1" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent" @checked($block->is_shared)>
                    Share on calendar (visible to people with access to your calendar)
                </label>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-5 py-4">
                <button type="button" x-on:click="editOpen = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Save Changes</button>
            </div>
        </form>
    </div>
</div>
