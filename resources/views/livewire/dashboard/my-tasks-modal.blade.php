<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="my-tasks-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div @class([
            'relative z-10 flex w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl',
            'h-[92vh]' => ($tasks['count'] ?? 0) > 6 || $showCreateForm || (($assignedByMe['items'] ?? []) !== []),
            'max-h-[72vh]' => ($tasks['count'] ?? 0) <= 6 && ! $showCreateForm && (($assignedByMe['items'] ?? []) === []),
        ])>
            <div class="border-b border-[#0F766E]/40 bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-5 py-3 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">My Tasks</p>
                        <h3 id="my-tasks-modal-title" class="mt-0.5 text-base font-semibold">Assigned to me</h3>
                        <p class="mt-0.5 text-xs text-slate-300">
                            {{ $tasks['count'] }} open task{{ $tasks['count'] === 1 ? '' : 's' }} waiting on you — from teammates, workflows, and system queues.
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            wire:click="toggleCreateForm"
                            class="rounded-md border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"
                        >
                            {{ $showCreateForm ? 'Hide form' : 'New task' }}
                        </button>
                        <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                    </div>
                </div>
            </div>

            @if (session('my_tasks_status'))
                <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-2 text-xs font-semibold text-emerald-800">
                    {{ session('my_tasks_status') }}
                </div>
            @endif

            @if ($showCreateForm)
                <form wire:submit="createTask" class="border-b border-slate-200 bg-slate-50">
                    <div class="grid gap-3 p-4 lg:grid-cols-12">
                        <div class="relative lg:col-span-4" x-data x-on:click.outside="$wire.set('assigneePickerOpen', false)">
                            <label for="my-task-assignee" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Assign to</label>
                            <input
                                id="my-task-assignee"
                                type="search"
                                wire:model.live.debounce.300ms="assigneeSearch"
                                wire:focus="openAssigneePicker"
                                autocomplete="off"
                                placeholder="Search member"
                                class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            @if ($assigneePickerOpen && strlen(trim($assigneeSearch)) >= 3)
                                <ul class="absolute z-20 mt-1 max-h-44 w-full overflow-y-auto rounded-md border border-slate-300 bg-white py-1 shadow-lg">
                                    @forelse ($assigneeOptions as $assignee)
                                        <li wire:key="my-task-assignee-{{ $assignee->id }}">
                                            <button type="button" wire:click="selectAssignee({{ $assignee->id }})" class="flex w-full flex-col px-3 py-2 text-left text-xs text-gw-primary hover:bg-gw-accent-soft">
                                                <span class="font-semibold">{{ $assignee->name }}</span>
                                                <span class="text-[11px] text-slate-500">{{ $assignee->email }}</span>
                                            </button>
                                        </li>
                                    @empty
                                        <li class="px-3 py-2 text-xs text-slate-500">No matching members.</li>
                                    @endforelse
                                </ul>
                            @endif
                            @error('assigneeId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-3">
                            <label for="my-task-category" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Category</label>
                            <select id="my-task-category" wire:model.live="taskCategorySelection" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                                <option value="{{ \App\Livewire\Dashboard\MyTasksModal::CREATE_ONE }}">Create one</option>
                            </select>
                            @if ($taskCategorySelection === \App\Livewire\Dashboard\MyTasksModal::CREATE_ONE)
                                <input
                                    type="text"
                                    wire:model="newCategoryName"
                                    placeholder="New category name"
                                    class="mt-2 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            @endif
                            @error('taskCategorySelection') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('newCategoryName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-3">
                            <label for="my-task-template" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Task template</label>
                            <select
                                id="my-task-template"
                                wire:model.live="taskTemplateSelection"
                                @disabled(! $taskCategorySelection)
                                class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:cursor-not-allowed disabled:bg-slate-100"
                            >
                                <option value="">{{ $taskCategorySelection ? 'Select task template' : 'Select a category first' }}</option>
                                @foreach ($libraryTasks as $libraryTask)
                                    <option value="{{ $libraryTask->id }}">{{ $libraryTask->title }}</option>
                                @endforeach
                                <option value="{{ \App\Livewire\Dashboard\MyTasksModal::CREATE_ONE }}">Create one</option>
                            </select>
                            @if ($taskTemplateSelection === \App\Livewire\Dashboard\MyTasksModal::CREATE_ONE)
                                <input
                                    type="text"
                                    wire:model="newTaskTemplateTitle"
                                    placeholder="New task title"
                                    class="mt-2 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            @endif
                            @error('taskTemplateSelection') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('newTaskTemplateTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label for="my-task-priority" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Priority</label>
                            <select id="my-task-priority" wire:model="priority" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="my-task-due-date" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Due date</label>
                            <input id="my-task-due-date" type="date" wire:model="dueDate" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @error('dueDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="relative lg:col-span-4" x-data x-on:click.outside="$wire.set('relatedPersonPickerOpen', false)">
                            <label for="my-task-related-person" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Related person</label>
                            <input
                                id="my-task-related-person"
                                type="search"
                                wire:model.live.debounce.300ms="relatedPersonSearch"
                                wire:focus="openRelatedPersonPicker"
                                autocomplete="off"
                                placeholder="Search prospect, client, or member"
                                class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            @if ($relatedPersonPickerOpen && strlen(trim($relatedPersonSearch)) >= 3)
                                <ul class="absolute z-20 mt-1 max-h-44 w-full overflow-y-auto rounded-md border border-slate-300 bg-white py-1 shadow-lg">
                                    @forelse ($relatedPersonOptions as $person)
                                        <li wire:key="my-task-related-person-{{ $person->key }}">
                                            <button
                                                type="button"
                                                wire:click="selectRelatedPerson(@js($person->label))"
                                                class="flex w-full flex-col px-3 py-2 text-left text-xs text-gw-primary hover:bg-gw-accent-soft"
                                            >
                                                <span class="font-semibold">{{ $person->label }}</span>
                                                <span class="text-[11px] text-slate-500">{{ $person->subtitle }}@if ($person->meta) · {{ $person->meta }}@endif</span>
                                            </button>
                                        </li>
                                    @empty
                                        <li class="px-3 py-2 text-xs text-slate-500">No matching people found.</li>
                                    @endforelse
                                </ul>
                            @endif
                            @error('relatedPerson') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label for="my-task-related-module" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Related module</label>
                            <select id="my-task-related-module" wire:model.live="relatedModuleSelection" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                <option value="">Select module</option>
                                @foreach ($relatedModules as $module)
                                    <option value="{{ $module }}">{{ $module }}</option>
                                @endforeach
                                <option value="{{ \App\Livewire\Dashboard\MyTasksModal::MODULE_OTHER }}">Other</option>
                            </select>
                            @if ($relatedModuleSelection === \App\Livewire\Dashboard\MyTasksModal::MODULE_OTHER)
                                <input
                                    type="text"
                                    wire:model="relatedModuleOther"
                                    placeholder="Custom module name"
                                    class="mt-2 h-9 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            @endif
                            @error('relatedModuleSelection') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('relatedModuleOther') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-9">
                            <label for="my-task-notes" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Notes</label>
                            <textarea id="my-task-notes" wire:model="additionalNotes" rows="2" placeholder="Optional context or instructions" class="mt-1 w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                            @error('additionalNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-end justify-end gap-2 lg:col-span-3">
                            <button type="button" wire:click="toggleCreateForm" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="createTask" class="rounded-md bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-3 py-2 text-xs font-semibold text-white hover:brightness-110 disabled:opacity-60">
                                <span wire:loading.remove wire:target="createTask">Create task</span>
                                <span wire:loading wire:target="createTask">Creating...</span>
                            </button>
                        </div>
                    </div>

                    @if (filled($selectedTaskDescription))
                        <div class="border-t border-slate-200 px-4 py-2 text-xs text-slate-600">
                            <span class="font-semibold text-gw-primary">Template:</span> {{ $selectedTaskDescription }}
                        </div>
                    @endif
                </form>
            @endif

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Assigned to me</p>
                    <p class="text-xs text-slate-600">Tasks others assigned to you appear here. Check them off when complete.</p>
                </div>

                <div class="p-3">
                    @forelse ($tasks['items'] as $task)
                    <div
                        wire:key="my-task-{{ $task['id'] ?? md5(($task['title'] ?? '').($task['meta'] ?? '')) }}"
                        @class([
                            'mb-2 rounded-md border px-3 py-2 transition',
                            ($task['highlight'] ?? false)
                                ? 'border-gw-accent/50 bg-[#FFFDF5]'
                                : 'border-slate-200 bg-white',
                        ])
                    >
                        <div class="grid grid-cols-[1.75rem_minmax(0,1fr)_auto] items-start gap-2">
                            <div class="pt-0.5">
                                @if (($task['completable'] ?? false) && filled($task['id'] ?? null))
                                    <input
                                        type="checkbox"
                                        wire:click="completeTask({{ $task['id'] }})"
                                        wire:confirm="Mark this task complete?"
                                        class="h-4 w-4 rounded border-slate-300 text-gw-primary focus:ring-gw-accent"
                                        aria-label="Mark {{ $task['title'] ?? 'task' }} complete"
                                    >
                                @else
                                    <input
                                        type="checkbox"
                                        disabled
                                        class="h-4 w-4 rounded border-slate-300 bg-slate-100 text-slate-300"
                                        title="Complete this task from its workflow"
                                        aria-label="Workflow task completion is handled in its source workflow"
                                    >
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex min-w-0 items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-gw-primary">{{ $task['title'] ?? 'Untitled task' }}</p>
                                    @if (filled($task['category'] ?? null))
                                        <span @class([
                                            'hidden shrink-0 rounded-full border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide sm:inline-flex',
                                            $task['category_accent'] ?? 'bg-slate-100 text-slate-700 border-slate-200',
                                        ])>{{ $task['category'] }}</span>
                                    @endif
                                </div>

                                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                    @if (filled($task['source'] ?? null))
                                        <span>{{ $task['source'] }}</span>
                                    @endif
                                    @if (filled($task['meta'] ?? null))
                                        <span>{{ $task['meta'] }}</span>
                                    @endif
                                    @if (filled($task['badge'] ?? null))
                                        <span @class([
                                            'rounded-full px-1.5 py-0.5 font-semibold uppercase tracking-wide',
                                            ($task['highlight'] ?? false) ? 'bg-gw-accent-soft text-gw-accent-dark' : 'bg-slate-100 text-slate-600',
                                        ])>{{ $task['badge'] }}</span>
                                    @endif
                                </div>

                                <p class="mt-0.5 line-clamp-1 text-xs text-slate-600">
                                    {{ filled($task['notes'] ?? null) ? $task['notes'] : ($task['subtitle'] ?? 'Open the related workflow for details.') }}
                                </p>
                            </div>

                            @if (filled($task['action_event'] ?? null) && ($task['action_event'] === 'open-invite-member-modal') && filled($task['invitation_id'] ?? null))
                                <button
                                    type="button"
                                    wire:click="openInvitationEmailModal({{ $task['invitation_id'] }})"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:brightness-110"
                                >
                                    {{ $task['action_label'] ?? 'Send invitation email' }}
                                </button>
                            @elseif (filled($task['url'] ?? null))
                                <a
                                    href="{{ $task['url'] }}"
                                    wire:navigate
                                    x-on:click="$wire.close()"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:brightness-110"
                                >
                                    {{ $task['action_label'] ?? 'Open' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                        <p class="text-sm font-semibold text-gw-primary">You are all caught up</p>
                        <p class="mt-1 text-xs text-slate-500">When someone assigns a task to you, it will show up in this list with who sent it.</p>
                    </div>
                @endforelse
                </div>

                @if (($assignedByMe['stats']['total'] ?? 0) > 0)
                    <livewire:tasks.assigned-by-me-panel :compact="true" panel-id="my-tasks-assigned-by-me-panel" :limit="15" />
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                <p class="text-xs text-slate-500">Workflow tasks open the right screen to finish the action. Assigned tasks can be checked off here.</p>
                @if (Route::has('tasks.index'))
                    <a
                        href="{{ route('tasks.index') }}"
                        wire:navigate
                        x-on:click="$wire.close()"
                        class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110"
                    >
                        Open task manager
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
