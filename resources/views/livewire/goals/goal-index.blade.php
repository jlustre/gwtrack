<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">My Goals</h2>
            <p class="text-sm text-slate-600">Track in-progress goals across all categories (includes off-track).</p>
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
            <button type="button" wire:click="refreshProgress" class="rounded-full border border-gw-accent bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-muted">
                Sync KPIs
            </button>
        </div>
    </div>

    <div class="grid gap-3 border-b border-slate-100 bg-slate-50/80 p-4 md:grid-cols-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search goals…" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            <option value="all">All statuses</option>
            <option value="active">In progress (Active + Off Track)</option>
            @foreach (config('goals.statuses', []) as $key => $label)
                @continue($key === 'active')
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="p-4">
        @switch($viewMode)
        @case('list')
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Goal</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Progress</th>
                            <th class="px-3 py-2">Deadline</th>
                            <th class="px-3 py-2">SMART</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($goals as $goal)
                            <tr wire:key="goal-row-{{ $goal->id }}">
                                <td class="px-3 py-3 font-semibold text-gw-primary">{{ $goal->name }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $goal->category?->name }}</td>
                                <td class="px-3 py-3">{{ $goal->formattedActual() }} / {{ $goal->formattedTarget() }} ({{ $goal->progressPercent() }}%)</td>
                                <td class="px-3 py-3 text-slate-600">{{ $goal->deadline_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $goal->smart_score }}%</td>
                                <td class="px-3 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('update', $goal)
                                            <button type="button" wire:click="editGoal({{ $goal->id }})" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary">Edit</button>
                                        @endcan
                                        @can('delete', $goal)
                                            <button type="button" wire:click="deleteGoal({{ $goal->id }})" wire:confirm="Delete this goal? This cannot be undone." class="text-xs font-semibold text-red-600 hover:text-red-800">Delete</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">No goals match your filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($editingGoalId)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:keydown.escape="cancelEdit">
                    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl" wire:click.outside="cancelEdit">
                        <form wire:submit="saveGoal" class="p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gw-primary">Edit goal</h3>
                                    <p class="mt-1 text-sm text-slate-600">Update details for this goal.</p>
                                </div>
                                <button type="button" wire:click="cancelEdit" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="Close">✕</button>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <x-input-label for="edit_goal_name" value="Goal name" />
                                    <x-text-input id="edit_goal_name" wire:model="editName" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('editName')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="edit_goal_description" value="Description" />
                                    <textarea id="edit_goal_description" wire:model="editDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                                    <x-input-error :messages="$errors->get('editDescription')" class="mt-1" />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="edit_goal_category" value="Category" />
                                        <select id="edit_goal_category" wire:model="editGoalCategoryId" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('editGoalCategoryId')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="edit_goal_status" value="Status" />
                                        <select id="edit_goal_status" wire:model="editStatus" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                            @foreach (config('goals.statuses', []) as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('editStatus')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="edit_goal_target" value="Target value" />
                                        <x-text-input id="edit_goal_target" type="number" step="0.01" wire:model="editTargetValue" class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('editTargetValue')" class="mt-1" />
                                    </div>

                                    @unless ($editHasAutomatedMetric)
                                        <div>
                                            <x-input-label for="edit_goal_actual" value="Current progress" />
                                            <x-text-input id="edit_goal_actual" type="number" step="0.01" wire:model="editActualValue" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('editActualValue')" class="mt-1" />
                                        </div>
                                    @else
                                        <div class="flex items-end">
                                            <p class="text-xs text-slate-500">Progress syncs automatically from connected KPIs. Use <strong>Sync KPIs</strong> to refresh.</p>
                                        </div>
                                    @endunless
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="edit_goal_starts" value="Start date" />
                                        <x-text-input id="edit_goal_starts" type="date" wire:model="editStartsAt" class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('editStartsAt')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="edit_goal_deadline" value="Deadline" />
                                        <x-text-input id="edit_goal_deadline" type="date" wire:model="editDeadlineAt" class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('editDeadlineAt')" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-wrap justify-end gap-2">
                                <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                                    <span wire:loading.remove wire:target="saveGoal">Save changes</span>
                                    <span wire:loading wire:target="saveGoal">Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @break

        @case('kanban')
            <div class="grid gap-4 md:grid-cols-3">
                @foreach (['active' => 'Active', 'off_track' => 'Off Track', 'completed' => 'Completed'] as $status => $label)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <h3 class="text-sm font-semibold text-gw-primary">{{ $label }}</h3>
                        <div class="mt-3 space-y-2">
                            @foreach ($goals->where('status', $status) as $goal)
                                <div wire:key="kanban-{{ $goal->id }}" class="rounded-lg border border-white bg-white p-3 shadow-sm">
                                    <p class="font-semibold text-gw-primary">{{ $goal->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $goal->category?->name }}</p>
                                    <div class="mt-2 h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-gw-accent" style="width: {{ $goal->progressPercent() }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @break

        @case('timeline')
            <div class="relative border-l-2 border-gw-accent/40 pl-6">
                @forelse ($timelineGoals as $goal)
                    <div wire:key="timeline-{{ $goal->id }}" class="relative mb-8">
                        <span class="absolute -left-[1.65rem] top-1 flex h-3 w-3 rounded-full bg-gw-accent ring-4 ring-white"></span>
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            {{ $goal->starts_at?->format('M j, Y') ?? $goal->created_at->format('M j, Y') }}
                            @if ($goal->deadline_at)
                                → {{ $goal->deadline_at->format('M j, Y') }}
                            @endif
                        </p>
                        <h3 class="mt-1 text-base font-semibold text-gw-primary">{{ $goal->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $goal->category?->name }} &middot; {{ $goal->progressPercent() }}%</p>
                        <div class="mt-2 h-1.5 max-w-xs rounded-full bg-slate-100">
                            <div class="h-1.5 rounded-full bg-gw-accent" style="width: {{ $goal->progressPercent() }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No goals to display on the timeline.</p>
                @endforelse
            </div>
            @break

        @case('progress')
            <div class="space-y-4">
                @forelse ($goals as $goal)
                    <div wire:key="progress-{{ $goal->id }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $goal->name }}</p>
                                <p class="text-xs text-slate-500">{{ $goal->category?->name }}</p>
                            </div>
                            <span class="text-lg font-bold text-gw-primary">{{ $goal->progressPercent() }}%</span>
                        </div>
                        <div class="mt-3 h-3 rounded-full bg-slate-100">
                            <div
                                @class([
                                    'h-3 rounded-full transition-all',
                                    'bg-emerald-500' => $goal->status === 'completed',
                                    'bg-amber-500' => $goal->status === 'off_track',
                                    'bg-gw-accent' => $goal->status === 'active',
                                    'bg-slate-400' => ! in_array($goal->status, ['completed', 'off_track', 'active']),
                                ])
                                style="width: {{ $goal->progressPercent() }}%"
                            ></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ $goal->formattedActual() }} of {{ $goal->formattedTarget() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No goals to chart.</p>
                @endforelse
            </div>
            @break

        @case('calendar')
            @php
                $eventsByDate = $calendarEvents->groupBy('date');
                $monthStart = now()->startOfMonth();
                $daysInMonth = $monthStart->daysInMonth;
                $paddingDays = $monthStart->dayOfWeek > 0 ? range(0, $monthStart->dayOfWeek - 1) : [];
                $monthDays = range(1, $daysInMonth);
            @endphp
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gw-primary">{{ $monthStart->format('F Y') }}</h3>
                <p class="text-xs text-slate-500">{{ $calendarEvents->count() }} events</p>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase text-slate-500">
                @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                    <div class="py-2">{{ $day }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                @foreach ($paddingDays as $pad)
                    <div wire:key="cal-pad-{{ $pad }}" class="min-h-[4.5rem] rounded-lg bg-slate-50/50"></div>
                @endforeach
                @foreach ($monthDays as $day)
                    @php
                        $dateKey = $monthStart->copy()->day($day)->toDateString();
                        $dayEvents = $eventsByDate->get($dateKey, collect());
                    @endphp
                    <div
                        wire:key="cal-{{ $dateKey }}"
                        class="min-h-[4.5rem] rounded-lg border p-1 text-left {{ $dateKey === now()->toDateString() ? 'border-gw-accent bg-gw-accent-soft/50' : 'border-slate-100 bg-white' }}"
                    >
                        <span class="text-xs font-semibold text-slate-600">{{ $day }}</span>
                        @foreach ($dayEvents->take(2) as $event)
                            <p class="mt-0.5 truncate rounded bg-gw-primary/10 px-1 text-[0.6rem] text-gw-primary" title="{{ $event['title'] }}">{{ $event['title'] }}</p>
                        @endforeach
                        @if ($dayEvents->count() > 2)
                            <p class="text-[0.6rem] text-slate-500">+{{ $dayEvents->count() - 2 }} more</p>
                        @endif
                    </div>
                @endforeach
            </div>
            @break

        @default
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($goals as $goal)
                    <article wire:key="goal-card-{{ $goal->id }}" class="rounded-xl border border-slate-200 bg-gradient-to-br from-white to-gw-accent-soft/30 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-bold uppercase {{ $goal->category?->accent_class ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                    {{ $goal->category?->name }}
                                </span>
                                <h3 class="mt-2 text-base font-semibold text-gw-primary">{{ $goal->name }}</h3>
                            </div>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $goal->status === 'completed',
                                'bg-amber-50 text-amber-800' => $goal->status === 'off_track',
                                'bg-sky-50 text-sky-700' => $goal->status === 'active',
                                'bg-slate-100 text-slate-600' => ! in_array($goal->status, ['completed', 'off_track', 'active']),
                            ])>{{ config('goals.statuses.'.$goal->status, $goal->status) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $goal->description ?: 'No description.' }}</p>
                        <div class="mt-4">
                            <div class="flex justify-between text-xs font-semibold text-slate-600">
                                <span>{{ $goal->formattedActual() }} / {{ $goal->formattedTarget() }}</span>
                                <span>{{ $goal->progressPercent() }}%</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $goal->progressPercent() }}%"></div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
                            <span>{{ ucfirst($goal->hierarchy_level) }}</span>
                            @if ($goal->deadline_at)
                                <span>&middot; Due {{ $goal->deadline_at->format('M j') }}</span>
                            @endif
                            <span>&middot; SMART {{ $goal->smart_score }}%</span>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 xl:col-span-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <p class="text-sm text-slate-600">No goals yet. Create your first SMART goal to start tracking performance.</p>
                        <button
                            type="button"
                            wire:click="$dispatch('goals-hub-set-tab', { tab: 'create' })"
                            class="mt-4 inline-flex rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
                        >
                            Create Goal
                        </button>
                    </div>
                @endforelse
            </div>
        @endswitch
    </div>
</div>
