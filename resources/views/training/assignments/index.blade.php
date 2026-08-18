<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <h1 class="mt-2 text-3xl font-semibold">My Assignments</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">Courses assigned to you with due dates and progress tracking.</p>
                </div>
                @can('manage training')
                    <a href="{{ route('training.assignments.manage') }}" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        Assign courses
                    </a>
                @endcan
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-3">
                @forelse ($rows as $row)
                    @php
                        $assignment = $row['assignment'];
                        $module = $row['module'];
                    @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="font-semibold text-gw-primary">{{ $module?->title ?? 'Course' }}</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ str($assignment->status)->replace('_', ' ')->title() }}
                                    @if ($assignment->due_at)
                                        · Due {{ $assignment->due_at->format('M j, Y') }}
                                    @endif
                                    @if ($assignment->assignedBy)
                                        · Assigned by {{ $assignment->assignedBy->name }}
                                    @endif
                                </p>
                                @if ($assignment->notes)
                                    <p class="mt-2 text-sm text-slate-600">{{ $assignment->notes }}</p>
                                @endif
                                <div class="mt-3 h-2 max-w-md rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $row['progress_percent'] }}%"></div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                                @if ($row['is_overdue'])
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-red-800">Overdue</span>
                                @endif
                                <span class="rounded-full bg-gw-primary px-2.5 py-1 text-[0.65rem] font-bold text-gw-accent">{{ $row['progress_percent'] }}%</span>
                                @if ($module)
                                    <a href="{{ route('training.courses.show', $module) }}" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                                        Open course
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">You have no course assignments yet. Explore the course catalog to start learning.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
