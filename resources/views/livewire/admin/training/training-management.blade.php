<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin · Training Operations"
        title="Training Management"
        description="Enroll associates into learning paths, or assign a task so they enroll themselves from the Training Center."
        class="rounded-xl"
    >
        <x-slot:actions>
            @can('manage training')
                <a href="{{ route('admin.training.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                    Training Setup
                </a>
            @endcan
            <a href="{{ route('training.paths.index') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                Open Training Center
            </a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('training_management_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('training_management_status') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="$set('tab', 'enroll')"
            @class([
                'rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-gw-primary text-white' => $tab === 'enroll',
                'border border-slate-200 bg-white text-gw-primary hover:border-gw-accent' => $tab !== 'enroll',
            ])
        >
            Enroll associate
        </button>
        <button
            type="button"
            wire:click="$set('tab', 'task')"
            @class([
                'rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-gw-primary text-white' => $tab === 'task',
                'border border-slate-200 bg-white text-gw-primary hover:border-gw-accent' => $tab !== 'task',
            ])
        >
            Assign enroll task
        </button>
    </div>

    @if ($tab === 'enroll')
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border-2 border-gw-primary/15 bg-white p-6 shadow-md">
                <h2 class="text-lg font-semibold text-gw-primary">Enroll associate in a path</h2>
                <p class="mt-1 text-sm text-slate-600">Immediately enrolls the associate into the selected learning path.</p>

                <form wire:submit="enrollAssociate" class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Associate</label>
                        <select wire:model="enrollUserId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select associate</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('enrollUserId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Learning path</label>
                        <select wire:model="enrollPathId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select path</option>
                            @foreach ($learningPaths as $path)
                                <option value="{{ $path->id }}">{{ $path->name }} ({{ $path->modules_count }} courses)</option>
                            @endforeach
                        </select>
                        @error('enrollPathId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        Enroll now
                    </button>
                </form>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Recent enrollments</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentEnrollments as $enrollment)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                            <p class="font-semibold text-gw-primary">{{ $enrollment->user?->name }} · {{ $enrollment->path?->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ str($enrollment->status)->replace('_', ' ')->title() }}
                                · {{ $enrollment->progress_percent }}%
                                @if ($enrollment->assignedBy)
                                    · by {{ $enrollment->assignedBy->name }}
                                @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">No path enrollments yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border-2 border-gw-primary/15 bg-white p-6 shadow-md">
                <h2 class="text-lg font-semibold text-gw-primary">Assign enroll task</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Creates a Training task for the associate to enroll themselves. Ideal for CFM coaching workflows.
                </p>

                <form wire:submit="assignEnrollTask" class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Associate</label>
                        <select wire:model="taskUserId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select associate</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('taskUserId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Learning path to enroll in</label>
                        <select wire:model="taskPathId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select path</option>
                            @foreach ($learningPaths as $path)
                                <option value="{{ $path->id }}">{{ $path->name }}</option>
                            @endforeach
                        </select>
                        @error('taskPathId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Due date</label>
                        <input type="date" wire:model="taskDueDate" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('taskDueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Coach notes (optional)</label>
                        <textarea wire:model="taskNotes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Why this path matters / what to complete first"></textarea>
                    </div>

                    <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                        Assign enroll task
                    </button>
                </form>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Recent enroll tasks</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentEnrollTasks as $taskUser)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                            <p class="font-semibold text-gw-primary">{{ $taskUser->assignee?->name }} · {{ $taskUser->task?->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ str($taskUser->status)->replace('_', ' ')->title() }}
                                @if ($taskUser->due_date) · Due {{ \Illuminate\Support\Carbon::parse($taskUser->due_date)->format('M j, Y') }} @endif
                                @if ($taskUser->assignor) · by {{ $taskUser->assignor->name }} @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">No enroll tasks assigned yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>
