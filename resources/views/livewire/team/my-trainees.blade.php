@php
    $statusStyles = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'new' => 'bg-violet-100 text-violet-800',
        'at_risk' => 'bg-rose-100 text-rose-800',
        'licensing' => 'bg-amber-100 text-amber-900',
        'fap' => 'bg-sky-100 text-sky-800',
        'promotion_ready' => 'bg-gw-accent-soft text-gw-primary',
        'inactive' => 'bg-slate-200 text-slate-600',
    ];

    $filters = [
        'all' => 'All',
        'at_risk' => 'At Risk',
        'new' => 'New',
        'fap' => 'FAP',
        'licensing' => 'Licensing',
        'promotion_ready' => 'Promotion Ready',
        'needs_contact' => 'Needs Contact',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    $progressBar = function (int $percent, string $tone = 'accent'): string {
        $fill = match ($tone) {
            'danger' => 'bg-rose-500',
            'warn' => 'bg-amber-500',
            'sky' => 'bg-sky-500',
            'emerald' => 'bg-emerald-500',
            default => 'bg-gw-accent',
        };

        return '<div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full '.$fill.'" style="width: '.max(0, min(100, $percent)).'%"></div></div>';
    };
@endphp

<div class="space-y-6">
    {{-- Hero --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-gw-primary via-[#102847] to-gw-primary shadow-lg">
        <div class="px-5 py-6 text-white sm:px-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gw-accent">Mentoring &amp; CFM</p>
                    <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">My Trainees</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Monitor every associate on your roster — progress, risk, tasks, and next coaching actions — so you can mentor with clarity.
                    </p>
                    <p class="mt-3 text-xs text-slate-400">
                        {{ $todayLabel }} · {{ $cfm->name }} · {{ $summary['capacity']['active'] }}/{{ $summary['capacity']['max'] }} capacity
                        · {{ $totalTrainees }} on roster
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $portalUrl }}" class="inline-flex items-center gap-1.5 rounded-md bg-gw-accent px-3.5 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">
                        Open CFM Portal
                    </a>
                    <a href="{{ $rosterExportUrl }}" class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15">
                        Export roster
                    </a>
                    <a href="{{ route('bookings.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15">
                        Schedule
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Summary stats --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Total Trainees', 'value' => $summary['total_trainees'], 'theme' => 'navy', 'subtitle' => 'On your roster', 'filter' => 'all'],
                ['label' => 'At Risk', 'value' => $summary['at_risk_trainees'], 'theme' => 'red', 'subtitle' => 'Needs attention now', 'filter' => 'at_risk'],
                ['label' => 'New (30 Days)', 'value' => $summary['new_associates_30d'], 'theme' => 'violet', 'subtitle' => 'Recent joins', 'filter' => 'new'],
                ['label' => 'FAP In Progress', 'value' => $summary['fap_in_progress'], 'theme' => 'cyan', 'subtitle' => 'Field apprenticeship', 'filter' => 'fap'],
                ['label' => 'Licensing', 'value' => $summary['licensing_in_progress'], 'theme' => 'gold', 'subtitle' => 'Licensing checklist', 'filter' => 'licensing'],
                ['label' => 'Promotion Ready', 'value' => $summary['promotion_ready'], 'theme' => 'emerald', 'subtitle' => 'Ready for next step', 'filter' => 'promotion_ready'],
                ['label' => 'Overdue Tasks', 'value' => $summary['overdue_tasks'], 'theme' => 'red', 'subtitle' => 'Past due coaching items'],
                ['label' => 'Upcoming Meetings', 'value' => $summary['upcoming_meetings'], 'theme' => 'cyan', 'subtitle' => 'Scheduled sessions'],
                ['label' => 'FAP Graduates', 'value' => $summary['fap_graduates'], 'theme' => 'emerald', 'subtitle' => 'Completed apprenticeship'],
                ['label' => 'Pending Approvals', 'value' => $summary['pending_approvals'], 'theme' => 'gold', 'subtitle' => 'Awaiting confirmation'],
            ] as $card)
                @if (! empty($card['filter']))
                    <button type="button" wire:click="setFilter(@js($card['filter']))" class="text-left transition hover:-translate-y-0.5">
                        <x-tracker-stat-card
                            :label="$card['label']"
                            :value="$card['value']"
                            :subtitle="$card['subtitle']"
                            :theme="$card['theme']"
                        />
                    </button>
                @else
                    <x-tracker-stat-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :subtitle="$card['subtitle']"
                        :theme="$card['theme']"
                    />
                @endif
            @endforeach
        </div>
    </section>

    {{-- Coaching priorities + pending --}}
    <div class="grid gap-4 xl:grid-cols-5">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
            <div class="border-b border-slate-100 bg-gw-primary px-5 py-3 text-white">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Coaching priorities</h2>
                <p class="mt-0.5 text-xs text-slate-300">Suggested next actions based on roster health</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($suggestions as $suggestion)
                    <li class="flex gap-3 px-5 py-3 text-sm text-slate-700">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-gw-accent"></span>
                        <span>{{ $suggestion }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                <h2 class="text-sm font-semibold text-gw-primary">Pending assignments</h2>
                <p class="mt-0.5 text-xs text-slate-500">Confirm before they appear as active trainees</p>
            </div>
            @if ($pendingAssignments === [])
                <p class="px-5 py-8 text-center text-sm text-slate-500">No pending assignments.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($pendingAssignments as $pending)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gw-primary">{{ $pending['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $pending['rank'] }} · Assigned by {{ $pending['assigned_by'] }}</p>
                            </div>
                            <a
                                href="{{ route('cfm.portal.assignments.review', $pending['id']) }}"
                                class="shrink-0 rounded-md bg-gw-accent px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover"
                            >
                                Review
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Toolbar --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-100 p-4 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gw-primary">Trainee roster</h2>
                    <p class="text-xs text-slate-500">
                        Showing {{ $trainees->count() }} of {{ $totalTrainees }}
                        @if ($statusFilter !== 'all') · filter: {{ $filters[$statusFilter] ?? $statusFilter }} @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="trainee-roster-search">Search trainees</label>
                    <input
                        id="trainee-roster-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search name, email, phone, location…"
                        class="w-full min-w-[16rem] rounded-lg border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent sm:w-72"
                    >

                    <select
                        wire:change="setSort($event.target.value)"
                        class="rounded-lg border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="priority" @selected($sortBy === 'priority')>Sort: Priority</option>
                        <option value="name" @selected($sortBy === 'name')>Sort: Name</option>
                        <option value="overall" @selected($sortBy === 'overall')>Sort: Overall progress</option>
                        <option value="fap" @selected($sortBy === 'fap')>Sort: FAP</option>
                        <option value="licensing" @selected($sortBy === 'licensing')>Sort: Licensing</option>
                        <option value="onboarding" @selected($sortBy === 'onboarding')>Sort: Onboarding</option>
                        <option value="activity" @selected($sortBy === 'activity')>Sort: Recent activity</option>
                        <option value="joined" @selected($sortBy === 'joined')>Sort: Newest join</option>
                    </select>

                    <div class="inline-flex rounded-lg border border-slate-200 p-0.5">
                        <button
                            type="button"
                            wire:click="setViewMode('table')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                'bg-gw-primary text-white' => $viewMode === 'table',
                                'text-slate-600 hover:bg-slate-50' => $viewMode !== 'table',
                            ])
                        >Table</button>
                        <button
                            type="button"
                            wire:click="setViewMode('cards')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                'bg-gw-primary text-white' => $viewMode === 'cards',
                                'text-slate-600 hover:bg-slate-50' => $viewMode !== 'cards',
                            ])
                        >Cards</button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
                @foreach ($filters as $key => $label)
                    <button
                        type="button"
                        wire:click="setFilter(@js($key))"
                        @class([
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-wide transition',
                            'bg-gw-accent text-gw-primary' => $statusFilter === $key,
                            'bg-slate-100 text-slate-600 hover:bg-slate-200' => $statusFilter !== $key,
                        ])
                    >
                        <span>{{ $label }}</span>
                        <span @class([
                            'rounded-full px-1.5 py-0.5 text-[0.65rem]',
                            'bg-gw-primary/15 text-gw-primary' => $statusFilter === $key,
                            'bg-white text-slate-500' => $statusFilter !== $key,
                        ])>{{ $filterCounts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($trainees->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-semibold text-gw-primary">
                    {{ $totalTrainees === 0 ? 'No trainees on your roster yet' : 'No trainees match this filter' }}
                </p>
                <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                    @if ($totalTrainees === 0)
                        Associates appear here once they are assigned to you as their Certified Field Mentor and the assignment is confirmed.
                    @else
                        Try clearing search or switching filters to see more of your roster.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    @if ($statusFilter !== 'all' || $search !== '')
                        <button type="button" wire:click="clearFilters" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Clear filters
                        </button>
                    @endif
                    <a href="{{ $portalUrl }}" class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Go to CFM Portal
                    </a>
                </div>
            </div>
        @elseif ($viewMode === 'cards')
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($trainees as $trainee)
                    <article wire:key="card-{{ $trainee['id'] }}" class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
                        <div class="flex items-start gap-3 border-b border-slate-100 p-4">
                            @if ($trainee['photo_url'])
                                <img src="{{ $trainee['photo_url'] }}" alt="" class="h-12 w-12 rounded-full object-cover ring-2 ring-white">
                            @else
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gw-primary text-sm font-bold text-gw-accent">{{ $trainee['initials'] }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-semibold text-gw-primary">{{ $trainee['name'] }}</h3>
                                        <p class="truncate text-xs text-slate-500">{{ $trainee['rank'] }} · {{ $trainee['location'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $statusStyles[$trainee['status']] ?? $statusStyles['active'] }}">
                                        {{ $trainee['status_label'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 p-4 text-xs">
                            <div>
                                <div class="mb-1 flex justify-between font-semibold text-slate-600">
                                    <span>Overall</span><span>{{ $trainee['overall_percent'] }}%</span>
                                </div>
                                {!! $progressBar($trainee['overall_percent']) !!}
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <p class="mb-1 font-semibold text-slate-500">Onboarding</p>
                                    {!! $progressBar($trainee['onboarding_percent'], 'emerald') !!}
                                    <p class="mt-1 text-slate-600">{{ $trainee['onboarding_percent'] }}%</p>
                                </div>
                                <div>
                                    <p class="mb-1 font-semibold text-slate-500">FAP</p>
                                    {!! $progressBar($trainee['fap_percent'], 'sky') !!}
                                    <p class="mt-1 text-slate-600">{{ $trainee['fap_percent'] }}%</p>
                                </div>
                                <div>
                                    <p class="mb-1 font-semibold text-slate-500">Licensing</p>
                                    {!! $progressBar($trainee['licensing_percent'], 'warn') !!}
                                    <p class="mt-1 text-slate-600">{{ $trainee['licensing_percent'] }}%</p>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-slate-600">
                                <div><dt class="text-slate-400">Joined</dt><dd class="font-medium">{{ $trainee['joined_at'] }}</dd></div>
                                <div><dt class="text-slate-400">Activity</dt><dd class="font-medium">{{ $trainee['last_activity_label'] }}</dd></div>
                                <div><dt class="text-slate-400">Open tasks</dt><dd class="font-medium">{{ $trainee['open_tasks'] }}@if($trainee['overdue_tasks']) <span class="text-rose-600">({{ $trainee['overdue_tasks'] }} overdue)</span>@endif</dd></div>
                                <div><dt class="text-slate-400">Next meeting</dt><dd class="font-medium">{{ $trainee['next_meeting_at'] ?? '—' }}</dd></div>
                                <div class="col-span-2"><dt class="text-slate-400">Sponsor</dt><dd class="font-medium">{{ $trainee['sponsor_name'] }}</dd></div>
                                @if ($trainee['needs_first_contact'])
                                    <div class="col-span-2 rounded-md bg-amber-50 px-2 py-1 font-semibold text-amber-800">First contact email not sent</div>
                                @endif
                            </dl>
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2 border-t border-slate-100 p-4">
                            <a href="{{ route('cfm.portal', ['trainee' => $trainee['id']]) }}" class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover">Coach in Portal</a>
                            @if ($trainee['assignment_id'])
                                <a href="{{ route('cfm.portal.trainees.checklist', $trainee['assignment_id']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Checklist</a>
                            @endif
                            <a href="{{ route('team.member', $trainee['id']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Profile</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Trainee</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 min-w-[9rem]">Progress</th>
                            <th class="px-4 py-3">Activity</th>
                            <th class="px-4 py-3">Tasks</th>
                            <th class="px-4 py-3">Next meeting</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($trainees as $trainee)
                            <tr wire:key="row-{{ $trainee['id'] }}" class="align-top hover:bg-gw-accent-soft/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        @if ($trainee['photo_url'])
                                            <img src="{{ $trainee['photo_url'] }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                        @else
                                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">{{ $trainee['initials'] }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gw-primary">{{ $trainee['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $trainee['rank'] }} · {{ $trainee['rank_name'] }}</p>
                                            <p class="text-xs text-slate-400">{{ $trainee['location'] }} · Joined {{ $trainee['joined_at'] }}</p>
                                            <p class="text-xs text-slate-400">Sponsor: {{ $trainee['sponsor_name'] }}</p>
                                            @if ($trainee['needs_first_contact'])
                                                <p class="mt-1 text-[0.7rem] font-semibold text-amber-700">Needs first contact</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide {{ $statusStyles[$trainee['status']] ?? $statusStyles['active'] }}">
                                        {{ $trainee['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-2 text-xs">
                                        <div>
                                            <div class="mb-0.5 flex justify-between text-slate-600"><span>Overall</span><span class="font-semibold">{{ $trainee['overall_percent'] }}%</span></div>
                                            {!! $progressBar($trainee['overall_percent']) !!}
                                        </div>
                                        <div>
                                            <div class="mb-0.5 flex justify-between text-slate-500"><span>Onboarding</span><span>{{ $trainee['onboarding_percent'] }}%</span></div>
                                            {!! $progressBar($trainee['onboarding_percent'], 'emerald') !!}
                                        </div>
                                        <div>
                                            <div class="mb-0.5 flex justify-between text-slate-500"><span>FAP</span><span>{{ $trainee['fap_percent'] }}%</span></div>
                                            {!! $progressBar($trainee['fap_percent'], 'sky') !!}
                                        </div>
                                        <div>
                                            <div class="mb-0.5 flex justify-between text-slate-500"><span>Licensing</span><span>{{ $trainee['licensing_percent'] }}%</span></div>
                                            {!! $progressBar($trainee['licensing_percent'], 'warn') !!}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <p class="font-semibold {{ $trainee['days_idle'] >= 7 ? 'text-rose-700' : 'text-slate-700' }}">{{ $trainee['last_activity_label'] }}</p>
                                    @if ($trainee['assignment_started_at'])
                                        <p class="mt-1 text-slate-400">Mentoring since {{ $trainee['assignment_started_at'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <p class="font-semibold text-slate-700">{{ $trainee['open_tasks'] }} open</p>
                                    @if ($trainee['overdue_tasks'] > 0)
                                        <p class="font-semibold text-rose-600">{{ $trainee['overdue_tasks'] }} overdue</p>
                                    @else
                                        <p class="text-slate-400">None overdue</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @if ($trainee['next_meeting_at'])
                                        <p class="font-semibold text-slate-700">{{ $trainee['next_meeting_at'] }}</p>
                                        <p class="text-slate-400">{{ $trainee['next_meeting_title'] }}</p>
                                    @else
                                        <p class="text-slate-400">None scheduled</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <p class="truncate font-medium">{{ $trainee['email'] }}</p>
                                    <p class="mt-0.5">{{ $trainee['phone'] ?: 'No phone' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-end gap-1.5">
                                        <a href="{{ route('cfm.portal', ['trainee' => $trainee['id']]) }}" class="rounded-md bg-gw-accent px-2.5 py-1 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover">Portal</a>
                                        @if ($trainee['assignment_id'])
                                            <a href="{{ route('cfm.portal.trainees.checklist', $trainee['assignment_id']) }}" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Checklist</a>
                                        @endif
                                        <a href="{{ route('team.member', $trainee['id']) }}" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Profile</a>
                                        <a href="{{ route('cfm.portal', ['trainee' => $trainee['id'], 'section' => 'tasks']) }}" class="text-xs font-semibold text-gw-primary hover:underline">Tasks</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
