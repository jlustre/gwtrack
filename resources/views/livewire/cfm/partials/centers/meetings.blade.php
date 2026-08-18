@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Coaching touchpoints</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $center['calendar_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">Calendar</a>
                <a href="{{ $center['bookings_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">Bookings</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Upcoming', 'value' => $center['stats']['upcoming'] ?? 0, 'theme' => 'amber'],
            ['label' => 'Scheduled', 'value' => $center['stats']['scheduled'] ?? 0, 'theme' => 'cyan'],
            ['label' => 'Completed', 'value' => $center['stats']['completed'] ?? 0, 'theme' => 'emerald'],
            ['label' => 'Calendar slots', 'value' => $center['stats']['calendar_bookings'] ?? 0, 'theme' => 'gold'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    @if (count($center['unlinked_bookings']) > 0)
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-sky-900">Calendar bookings not yet logged</h3>
            <ul class="mt-3 space-y-2">
                @foreach ($center['unlinked_bookings'] as $booking)
                    <li wire:key="cfm-unlinked-booking-{{ $booking['id'] }}" class="flex flex-col gap-2 rounded-lg border border-sky-100 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-gw-primary">{{ $booking['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ $booking['starts_at'] }} · {{ $booking['status'] }}</p>
                        </div>
                        <button type="button" wire:click="importBookingMeeting({{ $booking['id'] }})" class="rounded-lg bg-sky-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-800">Link as meeting</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-1">
            <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gw-primary">Schedule meeting</h3>
                <form wire:submit="createMeeting" class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Title</label>
                        <input type="text" wire:model="meetingTitle" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Weekly coaching check-in">
                        @error('meetingTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Type</label>
                        <select wire:model="meetingType" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($center['types'] as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Starts</label>
                        <input type="datetime-local" wire:model="meetingStartsAt" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('meetingStartsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Ends (optional)</label>
                        <input type="datetime-local" wire:model="meetingEndsAt" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Schedule</button>
                </form>
            </div>

            @if ($selectedMeetingId)
                <div class="rounded-xl border border-gw-primary/20 bg-gradient-to-br from-gw-primary to-[#102847] p-5 text-white shadow-lg">
                    <h3 class="text-sm font-semibold text-gw-accent">Meeting notes</h3>
                    <form wire:submit="saveMeetingNotes" class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-300">Summary</label>
                            <textarea wire:model="meetingNoteSummary" rows="4" class="mt-1 w-full rounded-md border-white/20 bg-white/10 text-sm text-white placeholder:text-slate-400 shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Key discussion points…"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-300">Action items (one per line)</label>
                            <textarea wire:model="meetingActionItems" rows="4" class="mt-1 w-full rounded-md border-white/20 bg-white/10 text-sm text-white placeholder:text-slate-400 shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Follow up on licensing exam prep"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Save notes</button>
                            <button type="button" wire:click="cancelMeetingNotes" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Cancel</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sessions</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('meetingStatusFilter', @js($key))"
                            @class([
                                'rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide',
                                'bg-gw-accent text-gw-primary' => $meetingStatusFilter === $key,
                                'bg-slate-100 text-slate-600 hover:bg-slate-200' => $meetingStatusFilter !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if (count($center['meetings']) === 0)
                <p class="p-6 text-sm text-slate-500">No meetings match this filter. Schedule one or link a calendar booking.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['meetings'] as $meeting)
                        <li wire:key="cfm-meeting-{{ $meeting['id'] }}" class="px-5 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                            'bg-emerald-100 text-emerald-800' => $meeting['status'] === 'completed',
                                            'bg-sky-100 text-sky-800' => $meeting['status'] === 'scheduled',
                                            'bg-red-100 text-red-800' => in_array($meeting['status'], ['cancelled', 'no_show'], true),
                                            'bg-slate-100 text-slate-700' => ! in_array($meeting['status'], ['completed', 'scheduled', 'cancelled', 'no_show'], true),
                                        ])>{{ str_replace('_', ' ', $meeting['status']) }}</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gw-accent-dark">{{ $meeting['type_label'] }}</span>
                                        @if ($meeting['from_booking'])
                                            <span class="text-[0.65rem] uppercase text-sky-600">From calendar</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 font-semibold text-gw-primary">{{ $meeting['title'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $meeting['starts_at'] }} – {{ $meeting['ends_at'] }}</p>
                                    @if ($meeting['meeting_link'])
                                        <a href="{{ $meeting['meeting_link'] }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-sky-700 hover:underline">Join meeting</a>
                                    @endif
                                    @if ($meeting['note_summary'])
                                        <p class="mt-3 text-sm text-slate-700">{{ $meeting['note_summary'] }}</p>
                                        @if (count($meeting['action_items']) > 0)
                                            <ul class="mt-2 list-inside list-disc text-xs text-slate-600">
                                                @foreach ($meeting['action_items'] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="selectMeetingForNotes({{ $meeting['id'] }})" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:border-gw-accent">Notes</button>
                                    @if ($meeting['status'] === 'scheduled')
                                        <button type="button" wire:click="updateMeetingStatus({{ $meeting['id'] }}, 'completed')" class="rounded-lg bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary hover:bg-gw-accent-hover">Complete</button>
                                        <button type="button" wire:click="updateMeetingStatus({{ $meeting['id'] }}, 'cancelled')" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Cancel</button>
                                    @endif
                                    <button type="button" wire:click="deleteMeeting({{ $meeting['id'] }})" wire:confirm="Remove this meeting?" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Delete</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
