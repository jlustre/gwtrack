<section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gw-primary">Availability Schedules</h2>
        <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-3 py-1 text-xs font-bold text-gw-primary">{{ $schedules->count() }}</span>
    </div>
    <form wire:submit="createSchedule" class="mt-4 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="schedule-name" class="block text-xs font-semibold text-slate-600">Schedule name</label>
            <input id="schedule-name" wire:model="scheduleName" required maxlength="120" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            @error('scheduleName') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="schedule-timezone" class="block text-xs font-semibold text-slate-600">Timezone</label>
            <input id="schedule-timezone" wire:model="scheduleTimezone" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            @error('scheduleTimezone') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
        </div>
        <button class="h-10 rounded-md bg-gw-primary px-4 text-sm font-semibold text-white">Create schedule</button>
    </form>

    @if ($schedules->isNotEmpty())
        <form wire:submit="addAvailabilityRule" class="mt-3 grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-5 sm:items-end">
            <div class="sm:col-span-2">
                <label for="rule-schedule" class="block text-xs font-semibold text-slate-600">Schedule</label>
                <select id="rule-schedule" wire:model="ruleScheduleId" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Choose schedule</option>
                    @foreach ($schedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->name }}</option>@endforeach
                </select>
                @error('ruleScheduleId') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="rule-weekday" class="block text-xs font-semibold text-slate-600">Day</label>
                <select id="rule-weekday" wire:model="ruleWeekday" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $name)
                        <option value="{{ $day }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rule-start" class="block text-xs font-semibold text-slate-600">From</label>
                <input id="rule-start" type="time" wire:model="ruleStartsAt" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div>
                <label for="rule-end" class="block text-xs font-semibold text-slate-600">Until</label>
                <input id="rule-end" type="time" wire:model="ruleEndsAt" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div class="sm:col-span-5">
                @error('ruleStartsAt') <p class="mb-2 text-xs text-rose-700">{{ $message }}</p> @enderror
                <button class="rounded-md border border-gw-primary px-4 py-2 text-sm font-semibold text-gw-primary">Add weekly window</button>
            </div>
        </form>

        <form wire:submit="saveAvailabilityOverride" class="mt-3 grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-6 sm:items-end">
            <div class="sm:col-span-2">
                <label for="override-schedule" class="block text-xs font-semibold text-slate-600">Schedule</label>
                <select id="override-schedule" wire:model="overrideScheduleId" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Choose schedule</option>
                    @foreach ($schedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="override-date" class="block text-xs font-semibold text-slate-600">Date</label>
                <input id="override-date" type="date" min="{{ now()->toDateString() }}" wire:model="overrideDate" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div>
                <label for="override-start" class="block text-xs font-semibold text-slate-600">From</label>
                <input id="override-start" type="time" wire:model="overrideStartsAt" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div>
                <label for="override-end" class="block text-xs font-semibold text-slate-600">Until</label>
                <input id="override-end" type="time" wire:model="overrideEndsAt" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div class="flex items-center gap-2">
                <input id="override-available" type="checkbox" wire:model="overrideAvailable" class="rounded border-slate-300">
                <label for="override-available" class="text-xs font-semibold text-slate-600">Available</label>
            </div>
            <div class="sm:col-span-5">
                <label for="override-reason" class="block text-xs font-semibold text-slate-600">Reason (optional)</label>
                <input id="override-reason" wire:model="overrideReason" maxlength="255" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            </div>
            <div><button class="h-10 rounded-md border border-gw-primary px-4 text-sm font-semibold text-gw-primary">Save date override</button></div>
        </form>
    @endif

    <form wire:submit="createBlackoutDate" class="mt-3 grid gap-3 rounded-lg border border-rose-200 bg-rose-50/50 p-3 sm:grid-cols-5 sm:items-end">
        <div>
            <label for="blackout-start" class="block text-xs font-semibold text-slate-600">Blackout begins</label>
            <input id="blackout-start" type="date" min="{{ now()->toDateString() }}" wire:model="blackoutStartsOn" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label for="blackout-end" class="block text-xs font-semibold text-slate-600">Ends (optional)</label>
            <input id="blackout-end" type="date" min="{{ now()->toDateString() }}" wire:model="blackoutEndsOn" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label for="blackout-reason" class="block text-xs font-semibold text-slate-600">Reason (optional)</label>
            <input id="blackout-reason" wire:model="blackoutReason" maxlength="255" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="blackoutRecurring" class="rounded border-slate-300"> Repeat annually
        </label>
        <button class="h-10 rounded-md border border-rose-400 px-4 text-sm font-semibold text-rose-800">Add blackout</button>
    </form>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                <tr><th class="px-3 py-2">Schedule</th><th class="px-3 py-2">Timezone</th><th class="px-3 py-2">Weekly windows</th><th class="px-3 py-2">Overrides</th><th class="px-3 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($schedules as $schedule)
                    <tr wire:key="schedule-{{ $schedule->id }}">
                        <td class="px-3 py-2 font-semibold text-gw-primary">{{ $schedule->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $schedule->timezone }}</td>
                        <td class="px-3 py-2 text-slate-600">
                            @forelse ($schedule->rules as $rule)
                                <span class="mr-2 inline-flex items-center gap-1">
                                    {{ \Illuminate\Support\Carbon::now()->startOfWeek()->addDays($rule->weekday - 1)->format('D') }} {{ $rule->starts_at->format('g:i A') }}–{{ $rule->ends_at->format('g:i A') }}
                                    <button type="button" wire:click="removeAvailabilityRule({{ $rule->id }})" class="text-rose-700" aria-label="Remove availability window">×</button>
                                </span>
                            @empty
                                <span class="text-amber-700">No weekly windows</span>
                            @endforelse
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ $schedule->overrides->count() }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $schedule->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No availability schedules created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($blackouts->isNotEmpty())
        <div class="mt-4">
            <h3 class="text-sm font-semibold text-gw-primary">Upcoming blackouts</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                @foreach ($blackouts as $blackout)
                    <li wire:key="blackout-{{ $blackout->id }}" class="flex items-center justify-between gap-3">
                        <span>{{ $blackout->starts_on->format('M j, Y') }}{{ $blackout->ends_on ? ' – '.$blackout->ends_on->format('M j, Y') : '' }}{{ $blackout->reason ? ' · '.$blackout->reason : '' }}</span>
                        <button type="button" wire:click="removeBlackoutDate({{ $blackout->id }})" class="text-xs font-semibold text-rose-700">Remove</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
