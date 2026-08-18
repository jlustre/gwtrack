@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Coaching actions</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">View profile</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Total tasks', 'value' => $center['stats']['total'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Open', 'value' => $center['stats']['open'] ?? 0, 'theme' => 'cyan'],
            ['label' => 'Completed', 'value' => $center['stats']['completed'] ?? 0, 'theme' => 'emerald'],
            ['label' => 'Overdue', 'value' => $center['stats']['overdue'] ?? 0, 'theme' => 'red'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm xl:col-span-1">
            <h3 class="text-sm font-semibold text-gw-primary">Assign new task</h3>
            <form wire:submit="createTask" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Title</label>
                    <input type="text" wire:model="taskTitle" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Call 10 prospects this week">
                    @error('taskTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Notes</label>
                    <textarea wire:model="taskNotes" rows="3" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Optional coaching instructions…"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Category</label>
                        <select wire:model="taskCategory" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($center['categories'] as $category)
                                <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Priority</label>
                        <select wire:model="taskPriority" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($center['priorities'] as $priority)
                                <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Due date</label>
                    <input type="date" wire:model="taskDueDate" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                </div>
                <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Create task</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Trainee tasks</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['all' => 'All', 'open' => 'Open', 'completed' => 'Done', 'overdue' => 'Overdue'] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('taskStatusFilter', @js($key))"
                            @class([
                                'rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide',
                                'bg-gw-accent text-gw-primary' => $taskStatusFilter === $key,
                                'bg-slate-100 text-slate-600 hover:bg-slate-200' => $taskStatusFilter !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if (count($center['tasks']) === 0)
                <p class="p-6 text-sm text-slate-500">No tasks match this filter.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['tasks'] as $task)
                        <li wire:key="cfm-task-{{ $task['id'] }}" class="px-5 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                            'bg-emerald-100 text-emerald-800' => $task['status'] === 'completed',
                                            'bg-sky-100 text-sky-800' => $task['status'] === 'in_progress',
                                            'bg-red-100 text-red-800' => $task['is_overdue'],
                                            'bg-slate-100 text-slate-700' => ! $task['is_overdue'] && ! in_array($task['status'], ['completed', 'in_progress'], true),
                                        ])>{{ $task['is_overdue'] ? 'Overdue' : str_replace('_', ' ', $task['status']) }}</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gw-accent-dark">{{ $task['priority'] }}</span>
                                        <span class="text-[0.65rem] uppercase text-slate-500">{{ $task['category'] }}</span>
                                    </div>
                                    <p class="mt-2 font-semibold text-gw-primary">{{ $task['title'] }}</p>
                                    @if ($task['notes'])
                                        <p class="mt-1 text-sm text-slate-600">{{ $task['notes'] }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-slate-500">Due {{ $task['due_date'] }} · Assigned {{ $task['created_at'] }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($task['status'] !== 'completed')
                                        <button type="button" wire:click="updateTaskStatus({{ $task['id'] }}, 'in_progress')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In progress</button>
                                        <button type="button" wire:click="updateTaskStatus({{ $task['id'] }}, 'completed')" class="rounded-lg bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary hover:bg-gw-accent-hover">Complete</button>
                                    @else
                                        <button type="button" wire:click="updateTaskStatus({{ $task['id'] }}, 'open')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Reopen</button>
                                    @endif
                                    <button type="button" wire:click="deleteTask({{ $task['id'] }})" wire:confirm="Delete this task?" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
