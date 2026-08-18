<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $module->category?->name ?? 'Course' }}</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ $module->title }}</h1>
                    @if ($module->description)
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $module->description }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-300">
                        <span class="rounded-full bg-white/10 px-2.5 py-1">{{ str($module->course_type ?? 'video')->replace('_', ' ')->title() }}</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-1">{{ str($module->difficulty ?? 'beginner')->title() }}</span>
                        @if ($module->duration_minutes)
                            <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $module->duration_minutes }} min</span>
                        @endif
                        @if ($module->sequential_required)
                            <span class="rounded-full bg-white/10 px-2.5 py-1">Sequential</span>
                        @endif
                        @if ($module->drip_enabled)
                            <span class="rounded-full bg-white/10 px-2.5 py-1">Drip schedule</span>
                        @endif
                    </div>
                </div>
                <div class="min-w-[12rem] rounded-xl border border-white/20 bg-white/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Your progress</p>
                    <p class="mt-1 text-3xl font-bold text-gw-accent">{{ $progressPercent }}%</p>
                    <div class="mt-3 h-2 rounded-full bg-white/20">
                        <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-semibold text-gw-primary">Course lessons</h2>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($module->drip_enabled)
                        Lessons unlock daily from {{ $courseStartDate->format('M j, Y') }}.
                    @elseif ($module->sequential_required)
                        Complete each lesson in order to unlock the next.
                    @else
                        Work through lessons at your own pace.
                    @endif
                </p>

                <ol class="mt-5 space-y-2">
                    @foreach ($lessonRows as $index => $row)
                        @php
                            $lesson = $row['lesson'];
                            $statusLabel = match ($row['status']) {
                                'completed' => 'Completed',
                                'in_progress' => 'In progress',
                                default => 'Not started',
                            };
                        @endphp
                        <li class="rounded-lg border border-slate-100 {{ $row['locked'] ? 'bg-slate-50/80' : 'bg-white' }} px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">{{ $index + 1 }}</span>
                                        @if ($row['locked'])
                                            <p class="font-semibold text-slate-500">{{ $lesson->title }}</p>
                                        @else
                                            <a href="{{ route('training.lessons.show', [$module, $lesson]) }}" class="font-semibold text-gw-primary transition hover:text-gw-accent">{{ $lesson->title }}</a>
                                        @endif
                                    </div>
                                    <p class="mt-1 pl-8 text-xs text-slate-500">
                                        {{ str($lesson->lesson_type ?? 'video')->replace('_', ' ')->title() }}
                                        · {{ $statusLabel }}
                                    </p>
                                    @if ($row['locked'] && $row['lock_reason'] === 'drip' && $row['unlocks_at'])
                                        <p class="mt-1 pl-8 text-xs font-semibold text-amber-700">Unlocks {{ $row['unlocks_at']->format('M j, Y') }}</p>
                                    @elseif ($row['locked'] && $row['lock_reason'] === 'sequential')
                                        <p class="mt-1 pl-8 text-xs font-semibold text-amber-700">Complete the previous lesson first</p>
                                    @endif
                                </div>
                                <div class="shrink-0">
                                    @if ($row['status'] === 'completed')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-emerald-800">Done</span>
                                    @elseif ($row['locked'])
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-slate-600">Locked</span>
                                    @else
                                        <a href="{{ route('training.lessons.show', [$module, $lesson]) }}" class="inline-flex rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-gw-primary-hover">
                                            {{ $row['status'] === 'in_progress' ? 'Continue' : 'Start' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="space-y-4">
                @php
                    $nextLesson = collect($lessonRows)->first(fn (array $row) => ! $row['locked'] && $row['status'] !== 'completed');
                @endphp
                @if ($nextLesson)
                    <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
                        <h3 class="font-semibold text-gw-primary">Continue learning</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $nextLesson['lesson']->title }}</p>
                        <a href="{{ route('training.lessons.show', [$module, $nextLesson['lesson']]) }}" class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                            {{ $nextLesson['status'] === 'in_progress' ? 'Resume lesson' : 'Start next lesson' }}
                        </a>
                    </div>
                @elseif ($progressPercent >= 100)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <h3 class="font-semibold text-emerald-900">Course complete</h3>
                        <p class="mt-1 text-sm text-emerald-800">You have finished all required lessons in this course.</p>
                    </div>
                @endif

                @if ($courseAssessment)
                    <div class="rounded-xl border border-gw-accent/30 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-gw-primary">Course assessment</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $courseAssessment->title }}</p>
                        <p class="mt-2 text-xs text-slate-500">Pass {{ $courseAssessment->passing_score }}%</p>
                        @if ($assessmentStats['passed'])
                            <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-emerald-800">Passed</span>
                        @elseif ($assessmentStats['best_score'] !== null)
                            <p class="mt-2 text-xs text-slate-500">Best score: {{ $assessmentStats['best_score'] }}%</p>
                        @endif
                        @if ($assessmentAccess['allowed'])
                            <a href="{{ route('assessments.show', $courseAssessment) }}" class="mt-4 inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                                {{ $assessmentStats['attempts_count'] > 0 ? 'Retake assessment' : 'Take assessment' }}
                            </a>
                        @else
                            <a href="{{ route('assessments.show', $courseAssessment) }}" class="mt-4 inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                                View assessment
                            </a>
                            @if ($assessmentAccess['reason'] === 'course_incomplete')
                                <p class="mt-2 text-xs font-semibold text-amber-700">Complete all lessons to unlock.</p>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
