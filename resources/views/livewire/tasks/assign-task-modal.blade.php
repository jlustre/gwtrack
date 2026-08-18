<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="assign-task-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl">
            <div class="border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Task Assignment</p>
                        <h3 id="assign-task-modal-title" class="mt-1 text-lg font-semibold">Assign a task</h3>
                        <p class="mt-1 text-sm text-slate-300">Choose a team member, task template, and due date.</p>
                    </div>
                    <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                </div>
            </div>

            <form wire:submit="assign" class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6">
                    <div
                        class="relative"
                        x-data
                        x-on:click.outside="$wire.set('assigneePickerOpen', false)"
                    >
                        <label for="assign-task-assignee" class="text-sm font-semibold text-gw-primary">Assign to</label>
                        <input
                            id="assign-task-assignee"
                            type="search"
                            wire:model.live.debounce.300ms="assigneeSearch"
                            wire:focus="openAssigneePicker"
                            autocomplete="off"
                            placeholder="Search by name or email (min. 3 characters)"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                        @if (strlen(trim($assigneeSearch)) > 0 && strlen(trim($assigneeSearch)) < 3)
                            <p class="mt-1 text-xs text-slate-500">Type at least 3 characters to search members.</p>
                        @endif
                        @if ($assigneePickerOpen && strlen(trim($assigneeSearch)) >= 3)
                            @if ($assigneeOptions->isNotEmpty())
                                <ul
                                    class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 shadow-lg"
                                    role="listbox"
                                >
                                    @foreach ($assigneeOptions as $assignee)
                                        <li role="option" wire:key="assign-task-assignee-{{ $assignee->id }}">
                                            <button
                                                type="button"
                                                wire:click="selectAssignee({{ $assignee->id }})"
                                                class="flex w-full flex-col px-3 py-2 text-left text-sm text-gw-primary hover:bg-gw-accent-soft"
                                            >
                                                <span class="font-semibold">{{ $assignee->name }}</span>
                                                <span class="text-xs text-slate-500">{{ $assignee->email }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-1 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                    No matching members found.
                                </p>
                            @endif
                        @endif
                        @error('assigneeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="assign-task-category" class="text-sm font-semibold text-gw-primary">Category</label>
                            <select
                                id="assign-task-category"
                                wire:model.live="taskCategorySelection"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
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
                                    class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            @endif
                            @error('taskCategorySelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('newCategoryName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="assign-task-template" class="text-sm font-semibold text-gw-primary">Task template</label>
                            <select
                                id="assign-task-template"
                                wire:model.live="taskTemplateSelection"
                                @disabled(! $taskCategorySelection)
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:cursor-not-allowed disabled:bg-slate-100"
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
                                    class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            @endif
                            @error('taskTemplateSelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('newTaskTemplateTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if (filled($selectedTaskDescription))
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Task description</p>
                            <p class="mt-1">{{ $selectedTaskDescription }}</p>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="assign-task-priority" class="text-sm font-semibold text-gw-primary">Priority</label>
                            <select
                                id="assign-task-priority"
                                wire:model="priority"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="assign-task-status" class="text-sm font-semibold text-gw-primary">Status</label>
                            <select
                                id="assign-task-status"
                                wire:model="status"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="assign-task-due-date" class="text-sm font-semibold text-gw-primary">Due date</label>
                            <input
                                id="assign-task-due-date"
                                type="date"
                                wire:model="dueDate"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            @error('dueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div
                            class="relative"
                            x-data
                            x-on:click.outside="$wire.set('relatedPersonPickerOpen', false)"
                        >
                            <label for="assign-task-related-person" class="text-sm font-semibold text-gw-primary">Related person</label>
                            <input
                                id="assign-task-related-person"
                                type="search"
                                wire:model.live.debounce.300ms="relatedPersonSearch"
                                wire:focus="openRelatedPersonPicker"
                                autocomplete="off"
                                placeholder="Search prospect, client, or member"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            @if ($relatedPersonPickerOpen && strlen(trim($relatedPersonSearch)) >= 3)
                                @if ($relatedPersonOptions->isNotEmpty())
                                    <ul
                                        class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 shadow-lg"
                                        role="listbox"
                                    >
                                        @foreach ($relatedPersonOptions as $person)
                                            <li role="option" wire:key="assign-task-related-person-{{ $person->key }}">
                                                <button
                                                    type="button"
                                                    wire:click="selectRelatedPerson(@js($person->label))"
                                                    class="flex w-full flex-col px-3 py-2 text-left text-sm text-gw-primary hover:bg-gw-accent-soft"
                                                >
                                                    <span class="font-semibold">{{ $person->label }}</span>
                                                    <span class="text-xs text-slate-500">{{ $person->subtitle }}@if ($person->meta) · {{ $person->meta }}@endif</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-1 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                        No matching people found.
                                    </p>
                                @endif
                            @endif
                            @error('relatedPerson') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="assign-task-related-module" class="text-sm font-semibold text-gw-primary">Related module</label>
                        <select
                            id="assign-task-related-module"
                            wire:model.live="relatedModuleSelection"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
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
                                class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                        @endif
                        @error('relatedModuleSelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('relatedModuleOther') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="assign-task-notes" class="text-sm font-semibold text-gw-primary">Additional notes</label>
                        <textarea
                            id="assign-task-notes"
                            wire:model="additionalNotes"
                            rows="4"
                            placeholder="Optional context or instructions for the assignee"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        @error('additionalNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <p class="text-xs text-slate-500">Assigned tasks appear in the member&apos;s task queue and task manager.</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="close"
                            class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-gw-accent px-4 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="assign">Assign task</span>
                            <span wire:loading wire:target="assign">Assigning…</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
