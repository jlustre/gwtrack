<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('calendar.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-gw-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                    Back to Calendar
                </a>
                <p class="mt-2 text-xs font-bold uppercase tracking-wide text-gw-accent">Calendar Settings</p>
                <h1 class="text-2xl font-semibold text-gw-primary">My Availability & Schedule Blocks</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Block recurring weekly times for work, personal activities, and other commitments. Shared blocks are visible to anyone who already has access to your calendar.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section id="weekly-blocks" class="rounded-lg border border-[#516070] bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gw-primary">Weekly Recurring Blocks</h2>
                    <p class="mt-1 text-sm text-slate-600">Set repeating unavailable windows. Blocks can span into the next day, such as Friday 6:00 PM through Saturday 8:00 PM.</p>
                </div>
            </div>

            @if ($weeklyBlocks->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach ($weeklyBlocks->groupBy('weekday') as $weekday => $blocks)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $weekdayLabels[$weekday] ?? 'Day '.$weekday }}</p>
                            <div class="mt-2 space-y-2">
                                @foreach ($blocks as $block)
                                    <div x-data="{ editOpen: false }" class="flex flex-col gap-2 rounded-md border border-white bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $block->typeColor() }}"></span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gw-primary">{{ $block->displayLabel() }}</p>
                                                <p class="text-xs text-slate-500">
                                                    {{ $block->weeklyRangeLabel($weekdayLabels) }}
                                                    · {{ $block->typeLabel() }}
                                                    @if ($block->is_shared)
                                                        · Shared
                                                    @else
                                                        · Private
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button type="button" x-on:click="editOpen = true" class="rounded-md border border-gw-accent/40 px-2.5 py-1 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-soft">View / Edit</button>
                                            <form method="POST" action="{{ route('calendar.schedule-blocks.destroy', $block) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
                                                <button type="submit" class="rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50" onclick="return confirm('Remove this weekly block?')">Remove</button>
                                            </form>
                                        </div>
                                        @include('events.partials.schedule-block-edit-modal', [
                                            'block' => $block,
                                            'blockTypes' => $blockTypes,
                                            'weekdayLabels' => $weekdayLabels,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    No weekly blocks yet. Add your work schedule or personal time below.
                </p>
            @endif

            <form method="POST" action="{{ route('calendar.schedule-blocks.store') }}" class="mt-5 space-y-4 rounded-lg border border-gw-accent/30 bg-gw-accent-soft p-4">
                @csrf
                <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Add Weekly Block</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="block_type" class="block text-xs font-semibold text-gw-primary">Type</label>
                        <select id="block_type" name="block_type" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($blockTypes as $key => $type)
                                <option value="{{ $key }}" @selected(old('block_type') === $key)>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="label" class="block text-xs font-semibold text-gw-primary">Label (optional)</label>
                        <input id="label" name="label" value="{{ old('label') }}" placeholder="e.g. Day job, Gym" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="weekday" class="block text-xs font-semibold text-gw-primary">From day</label>
                        <select id="weekday" name="weekday" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($weekdayLabels as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('weekday', 1) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="starts_at" class="block text-xs font-semibold text-gw-primary">From time</label>
                        <input id="starts_at" type="time" name="starts_at" value="{{ old('starts_at', '09:00') }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="end_weekday" class="block text-xs font-semibold text-gw-primary">To day</label>
                        <select id="end_weekday" name="end_weekday" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Same day</option>
                            @foreach ($weekdayLabels as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('end_weekday') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ends_at" class="block text-xs font-semibold text-gw-primary">To time</label>
                        <input id="ends_at" type="time" name="ends_at" value="{{ old('ends_at', '17:00') }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div class="flex items-end sm:col-span-2 lg:col-span-3">
                        <label class="flex items-center gap-2 text-sm text-gw-primary">
                            <input type="checkbox" name="is_shared" value="1" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent" @checked(old('is_shared', '1'))>
                            Share on calendar (visible to people with access to your calendar)
                        </label>
                    </div>
                </div>
                @if ($errors->any())
                    <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Add Weekly Block</button>
            </form>
        </section>

        <section id="date-blocks" class="rounded-lg border border-[#516070] bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gw-primary">Date & Time Range Blocks</h2>
            <p class="mt-1 text-sm text-slate-600">Block a single day or a date range — including multi-day spans like Friday 6:00 PM through Saturday 8:00 PM, or all-day vacations.</p>

            @if ($blockOverrides->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach ($blockOverrides as $override)
                        <div x-data="{ editOpen: false }" class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $override->typeColor() }}"></span>
                                <div>
                                    <p class="text-sm font-semibold text-gw-primary">{{ $override->displayLabel() }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $override->rangeLabel() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" x-on:click="editOpen = true" class="rounded-md border border-[#516070]/30 px-2.5 py-1 text-xs font-semibold text-gw-primary hover:bg-white">View / Edit</button>
                                <form method="POST" action="{{ route('calendar.schedule-block-overrides.destroy', $override) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
                                    <button type="submit" class="rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50" onclick="return confirm('Remove this date block?')">Remove</button>
                                </form>
                            </div>
                            @include('events.partials.schedule-block-override-edit-modal', [
                                'override' => $override,
                                'blockTypes' => $blockTypes,
                            ])
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('calendar.schedule-block-overrides.store') }}" class="mt-5 space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4" x-data="{ allDay: @js((bool) old('is_all_day')) }">
                @csrf
                <input type="hidden" name="return_to" value="{{ route('calendar.settings') }}">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Add Date Range Block</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="block_date" class="block text-xs font-semibold text-gw-primary">From date</label>
                        <input id="block_date" type="date" name="block_date" value="{{ old('block_date') }}" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label for="block_end_date" class="block text-xs font-semibold text-gw-primary">To date</label>
                        <input id="block_end_date" type="date" name="block_end_date" value="{{ old('block_end_date') }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Same as start if empty">
                        <p class="mt-1 text-[0.65rem] text-slate-500">Leave blank for a single day.</p>
                    </div>
                    <div>
                        <label for="override_block_type" class="block text-xs font-semibold text-gw-primary">Type</label>
                        <select id="override_block_type" name="block_type" required class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($blockTypes as $key => $type)
                                <option value="{{ $key }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="override_label" class="block text-xs font-semibold text-gw-primary">Label</label>
                        <input id="override_label" name="label" value="{{ old('label') }}" placeholder="e.g. Vacation, Weekend trip" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="flex items-center gap-2 text-sm text-gw-primary">
                            <input type="checkbox" name="is_all_day" value="1" x-model="allDay" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                            All day (entire date range)
                        </label>
                    </div>
                    <div x-show="!allDay" x-cloak>
                        <label for="override_starts_at" class="block text-xs font-semibold text-gw-primary">From time</label>
                        <input id="override_starts_at" type="time" name="starts_at" value="{{ old('starts_at', '09:00') }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div x-show="!allDay" x-cloak>
                        <label for="override_ends_at" class="block text-xs font-semibold text-gw-primary">To time</label>
                        <input id="override_ends_at" type="time" name="ends_at" value="{{ old('ends_at', '17:00') }}" class="mt-1 w-full rounded-md border-[#516070]/30 text-sm focus:border-gw-accent focus:ring-gw-accent">
                        <p class="mt-1 text-[0.65rem] text-slate-500">End time applies on the last day of the range.</p>
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-[#132B4B]">Add Date Range Block</button>
            </form>
        </section>

        <section class="rounded-lg border border-[#516070] bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gw-primary">General Preferences</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Default View</p>
                    <p class="mt-1 text-lg font-semibold text-gw-primary">{{ str($preference->default_view)->headline() }}</p>
                </div>
                <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Timezone</p>
                    <p class="mt-1 text-lg font-semibold text-gw-primary">{{ $preference->timezone }}</p>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
