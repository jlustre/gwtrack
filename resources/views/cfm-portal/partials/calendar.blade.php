<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 text-lg font-semibold text-gw-primary">Calendar &amp; Availability</h3>

    @if (! empty($profile['calendarPreview']))
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Booked this week</dt><dd class="font-medium text-gw-primary">{{ $profile['calendarPreview']['bookedSessions'] ?? 0 }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Open slots this week</dt><dd class="font-medium text-gw-primary">{{ $profile['calendarPreview']['slotsThisWeek'] ?? 0 }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Calendar busyness</dt><dd class="font-medium text-gw-primary">{{ $profile['calendarBusyness'] }}%</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Overdue tasks</dt><dd @class(['font-medium', 'text-red-700' => $profile['overdueTasks'] > 0, 'text-gw-primary' => $profile['overdueTasks'] === 0])>{{ $profile['overdueTasks'] }}</dd></div>
        </dl>

        @if (! empty($profile['nextSlots']))
            <div class="mt-4 border-t border-slate-200 pt-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming open slots</p>
                <ul class="space-y-1 text-xs text-slate-600">
                    @foreach ($profile['nextSlots'] as $slot)
                        <li>· {{ $slot }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($profile['calendarPreview']['conflictWarning']))
            <p class="mt-4 text-sm font-medium text-red-700">Calendar conflict warning — review your schedule.</p>
        @endif
    @else
        <p class="text-sm text-slate-500">No calendar data available.</p>
    @endif

    @if ($portal['canEditProfile'] ?? false)
        <div class="mt-6 border-t border-slate-200 pt-5">
            <div class="mb-3">
                <p class="text-sm font-semibold text-gw-primary">Share My Calendar</p>
                <p class="mt-1 text-xs text-slate-500">Let trainees and your agency owner see your scheduled events on their calendar. Private events stay hidden.</p>
            </div>

            <form method="POST" action="{{ route('cfm.portal.calendar-sharing.update') }}" class="space-y-3">
                @csrf
                @method('PATCH')

                <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="share_calendar_with_apprentices"
                        value="1"
                        class="mt-0.5 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                        @checked(old('share_calendar_with_apprentices', $profile['shareCalendarWithApprentices'] ?? true))
                    >
                    <span>
                        <span class="font-medium text-gw-primary">Share with my trainees</span>
                        <span class="mt-1 block text-xs text-slate-500">Active apprentices can view your non-private calendar events.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="share_calendar_with_agency_owner"
                        value="1"
                        class="mt-0.5 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                        @checked(old('share_calendar_with_agency_owner', $profile['shareCalendarWithAgencyOwner'] ?? false))
                    >
                    <span>
                        <span class="font-medium text-gw-primary">Share with agency owner</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            @if (($profile['agencyOwner'] ?? '—') !== '—')
                                {{ $profile['agencyOwner'] }} can view your non-private calendar events.
                            @else
                                Your agency owner can view your non-private calendar events when assigned.
                            @endif
                        </span>
                    </span>
                </label>

                <button type="submit" class="inline-flex items-center rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                    Save Sharing Preferences
                </button>
            </form>
        </div>
    @endif
</div>
