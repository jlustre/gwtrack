<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach ([
            ['label' => 'Members with goals', 'value' => $summary['member_count'], 'theme' => 'navy'],
            ['label' => 'Total goals', 'value' => $summary['total_goals'], 'theme' => 'slate'],
            ['label' => 'Active', 'value' => $summary['active'], 'theme' => 'cyan'],
            ['label' => 'Completed', 'value' => $summary['completed'], 'theme' => 'emerald'],
            ['label' => 'Off track', 'value' => $summary['off_track'], 'theme' => 'amber'],
            ['label' => 'Avg progress', 'value' => $summary['avg_progress'].'%', 'theme' => 'gold'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    @if ($offTrackGoals->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-amber-900">{{ $offTrackGoals->count() }} goal{{ $offTrackGoals->count() === 1 ? '' : 's' }} need attention</p>
                    <p class="mt-1 text-xs text-amber-800">Goals that are off track or falling behind their timeline.</p>
                </div>
                <button type="button" wire:click="setViewMode('off_track')" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                    Review now
                </button>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Team goal visibility</h2>
                <p class="text-sm text-slate-600">Monitor downline performance, deadlines, and coaching priorities.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($viewModes as $key => $label)
                    <button
                        type="button"
                        wire:click="setViewMode('{{ $key }}')"
                        @class([
                            'rounded-full px-3 py-1 text-xs font-semibold transition',
                            'bg-gw-primary text-white' => $viewMode === $key,
                            'border border-slate-300 text-slate-600 hover:border-gw-accent' => $viewMode !== $key,
                        ])
                    >{{ $label }}</button>
                @endforeach
                @if ($canCoach)
                    <button
                        type="button"
                        wire:click="$dispatch('goals-hub-set-tab', { tab: 'coaching' })"
                        class="rounded-full border border-gw-accent bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-muted"
                    >
                        CFM Coaching
                    </button>
                @endif
            </div>
        </div>

        <div class="grid gap-3 border-b border-slate-100 bg-slate-50/80 p-4 lg:grid-cols-6">
            <select wire:model.live="scope" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-1">
                <option value="personal">My goals only</option>
                <option value="directs">Direct recruits</option>
                <option value="downline">Full downline</option>
            </select>
            <select wire:model.live="memberFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-1">
                <option value="">All members</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-1">
                <option value="all">All statuses</option>
                @foreach (config('goals.statuses', []) as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-1">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search member or goal…" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-2">
        </div>

        <div class="p-4">
            @if ($viewMode === 'members')
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($memberRollup as $rollup)
                        <article wire:key="member-roll-{{ $rollup['user']->id }}" class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-semibold text-gw-primary">{{ $rollup['user']->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $rollup['goal_count'] }} goal{{ $rollup['goal_count'] === 1 ? '' : 's' }} &middot; {{ $rollup['avg_progress'] }}% avg</p>
                                </div>
                                @if ($rollup['off_track_count'] > 0)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $rollup['off_track_count'] }} off track</span>
                                @endif
                            </div>
                            <div class="mt-4 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $rollup['avg_progress'] }}%"></div>
                            </div>
                            <ul class="mt-4 space-y-2">
                                @foreach ($rollup['goals']->take(4) as $goal)
                                    <li wire:key="rollup-goal-{{ $goal->id }}" class="flex items-center justify-between gap-2 text-sm">
                                        <span class="truncate text-gw-primary">{{ $goal->name }}</span>
                                        <span class="shrink-0 text-xs font-semibold text-slate-500">{{ $goal->progressPercent() }}%</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($rollup['goals']->count() > 4)
                                <p class="mt-2 text-xs text-slate-500">+{{ $rollup['goals']->count() - 4 }} more</p>
                            @endif
                            <button type="button" wire:click="selectMember({{ $rollup['user']->id }})" class="mt-4 text-xs font-semibold text-gw-accent-dark hover:underline">
                                View all goals
                            </button>
                        </article>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <p class="text-sm text-slate-600">No team members with goals match your filters.</p>
                            <p class="mt-2 text-xs text-slate-500">Encourage your team to create SMART goals from the Goals hub.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Member</th>
                                <th class="px-4 py-3">Goal</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Progress</th>
                                <th class="px-4 py-3">Deadline</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($goals as $goal)
                                <tr wire:key="team-goal-{{ $goal->id }}" @class(['bg-amber-50/40' => $goal->status === 'off_track' || $goal->isOffTrack()])>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gw-primary">{{ $goal->user?->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $goal->user?->rank?->code ?? 'Member' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gw-primary">{{ $goal->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ ucfirst($goal->hierarchy_level) }} &middot; SMART {{ $goal->smart_score }}%</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $goal->category?->name }}</td>
                                    <td class="px-4 py-3">
                                        <div class="min-w-[8rem]">
                                            <div class="flex justify-between text-xs font-semibold text-slate-600">
                                                <span>{{ $goal->formattedActual() }} / {{ $goal->formattedTarget() }}</span>
                                                <span>{{ $goal->progressPercent() }}%</span>
                                            </div>
                                            <div class="mt-1 h-1.5 rounded-full bg-slate-100">
                                                <div @class([
                                                    'h-1.5 rounded-full',
                                                    'bg-emerald-500' => $goal->status === 'completed',
                                                    'bg-amber-500' => $goal->status === 'off_track' || $goal->isOffTrack(),
                                                    'bg-gw-accent' => ! in_array($goal->status, ['completed', 'off_track']) && ! $goal->isOffTrack(),
                                                ]) style="width: {{ $goal->progressPercent() }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $goal->deadline_at?->format('M j, Y') ?? '—' }}
                                        @if ($goal->deadline_at && $goal->deadline_at->isPast() && $goal->status !== 'completed')
                                            <span class="mt-0.5 block text-xs font-semibold text-red-600">Overdue</span>
                                        @elseif ($goal->deadline_at && $goal->deadline_at->lte(now()->addDays(7)) && $goal->status !== 'completed')
                                            <span class="mt-0.5 block text-xs font-semibold text-amber-700">Due soon</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-700' => $goal->status === 'completed',
                                            'bg-amber-50 text-amber-800' => $goal->status === 'off_track',
                                            'bg-sky-50 text-sky-700' => $goal->status === 'active',
                                            'bg-slate-100 text-slate-600' => ! in_array($goal->status, ['completed', 'off_track', 'active']),
                                        ])>{{ config('goals.statuses.'.$goal->status, $goal->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" wire:click="toggleGoal({{ $goal->id }})" class="text-xs font-semibold text-gw-accent-dark hover:underline">
                                            {{ $expandedGoalId === $goal->id ? 'Hide' : 'Details' }}
                                        </button>
                                    </td>
                                </tr>
                                @if ($expandedGoalId === $goal->id)
                                    <tr wire:key="team-goal-detail-{{ $goal->id }}" class="bg-slate-50/80">
                                        <td colspan="7" class="px-4 py-4">
                                            <div class="grid gap-4 lg:grid-cols-3">
                                                <div class="lg:col-span-2">
                                                    <p class="text-xs font-semibold uppercase text-slate-500">Description</p>
                                                    <p class="mt-1 text-sm text-slate-700">{{ $goal->description ?: 'No description provided.' }}</p>
                                                    @if ($goal->milestones->isNotEmpty())
                                                        <p class="mt-4 text-xs font-semibold uppercase text-slate-500">Milestones</p>
                                                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                                            @foreach ($goal->milestones as $milestone)
                                                                <li wire:key="milestone-{{ $milestone->id }}">
                                                                    {{ $milestone->name }}
                                                                    @if ($milestone->due_at)
                                                                        <span class="text-xs text-slate-500">(due {{ $milestone->due_at->format('M j') }})</span>
                                                                    @endif
                                                                    @if ($milestone->isComplete())
                                                                        <span class="text-xs font-semibold text-emerald-600">Done</span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                                <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                                                    <div>
                                                        <p class="text-xs uppercase text-slate-500">Accountability partner</p>
                                                        <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $goal->accountabilityPartner?->name ?? 'None assigned' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs uppercase text-slate-500">Started</p>
                                                        <p class="mt-1 text-sm text-slate-700">{{ $goal->starts_at?->format('M j, Y') ?? '—' }}</p>
                                                    </div>
                                                    @if ($goal->metric_key)
                                                        <div>
                                                            <p class="text-xs uppercase text-slate-500">Automated KPI</p>
                                                            <p class="mt-1 text-sm text-slate-700">{{ config('goals.metrics.'.$goal->metric_key.'.label', $goal->metric_key) }}</p>
                                                        </div>
                                                    @endif
                                                    @if ($canCoach)
                                                        <button type="button" wire:click="$dispatch('goals-hub-set-tab', { tab: 'coaching' })" class="inline-flex text-xs font-semibold text-gw-accent-dark hover:underline">Open CFM coaching</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center">
                                        <p class="text-sm text-slate-600">
                                            @if ($viewMode === 'off_track')
                                                No off-track goals in this scope. Your team is on pace.
                                            @else
                                                No team goals found for this filter.
                                            @endif
                                        </p>
                                        <p class="mt-2 text-xs text-slate-500">Try widening scope to full downline or changing the status filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($traineeGoals->isNotEmpty())
        <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft/40 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Your trainee goals</h3>
                    <p class="mt-1 text-xs text-slate-600">Active goals from apprentices you are mentoring.</p>
                </div>
                @if ($canCoach)
                    <button type="button" wire:click="$dispatch('goals-hub-set-tab', { tab: 'coaching' })" class="rounded-lg border border-gw-accent bg-white px-3 py-1.5 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-soft">Manage coaching notes</button>
                @endif
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($traineeGoals->take(6) as $goal)
                    <div wire:key="trainee-goal-{{ $goal->id }}" class="rounded-lg border border-white/80 bg-white/90 px-4 py-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-gw-accent">{{ $goal->user?->name }}</p>
                        <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $goal->name }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-600">
                            <span>{{ $goal->category?->name }}</span>
                            <span class="font-semibold">{{ $goal->progressPercent() }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
