<div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-lg rounded-xl border border-[#516070]/50 bg-white shadow-xl" x-on:click.outside="editOpen = false">
        <form method="POST" action="{{ route('calendar.schedule-block-overrides.update', $override) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Date Range Block</p>
                        <h3 class="text-lg font-semibold text-gw-primary">View / Edit Details</h3>
                    </div>
                    <button type="button" x-on:click="editOpen = false" class="gw-icon-btn-close" aria-label="Close date block details">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                    </button>
                </div>
                <p class="mt-2 text-sm text-slate-500">{{ $override->displayLabel() }} · {{ $override->rangeLabel() }}</p>
            </div>
            <div class="space-y-4 px-5 py-5" x-data="{ allDay: @js($override->is_all_day) }">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="edit-override-date-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">From date</label>
                        <input id="edit-override-date-{{ $override->id }}" type="date" name="block_date" value="{{ $override->block_date->format('Y-m-d') }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="edit-override-end-date-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">To date</label>
                        <input id="edit-override-end-date-{{ $override->id }}" type="date" name="block_end_date" value="{{ $override->block_end_date?->format('Y-m-d') }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit-override-type-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">Type</label>
                        <select id="edit-override-type-{{ $override->id }}" name="block_type" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($blockTypes as $key => $type)
                                <option value="{{ $key }}" @selected($override->block_type === $key)>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit-override-label-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">Label</label>
                        <input id="edit-override-label-{{ $override->id }}" name="label" value="{{ $override->label }}" placeholder="e.g. Vacation" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit-override-reason-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">Notes (optional)</label>
                        <input id="edit-override-reason-{{ $override->id }}" name="reason" value="{{ $override->reason }}" placeholder="Additional details" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-gw-primary">
                            <input type="checkbox" name="is_all_day" value="1" x-model="allDay" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                            All day (entire date range)
                        </label>
                    </div>
                    <div x-show="!allDay" x-cloak>
                        <label for="edit-override-starts-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">From time</label>
                        <input id="edit-override-starts-{{ $override->id }}" type="time" name="starts_at" value="{{ $override->starts_at ? substr((string) $override->starts_at, 0, 5) : '09:00' }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div x-show="!allDay" x-cloak>
                        <label for="edit-override-ends-{{ $override->id }}" class="block text-xs font-semibold text-gw-primary">To time</label>
                        <input id="edit-override-ends-{{ $override->id }}" type="time" name="ends_at" value="{{ $override->ends_at ? substr((string) $override->ends_at, 0, 5) : '17:00' }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-5 py-4">
                <button type="button" x-on:click="editOpen = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-[#132B4B]">Save Changes</button>
            </div>
        </form>
    </div>
</div>
