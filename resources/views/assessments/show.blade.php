<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <a href="{{ route('assessments.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Assessments</a>
            <h1 class="mt-2 text-3xl font-semibold">{{ $assessment->title }}</h1>
            @if ($assessment->description)
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $assessment->description }}</p>
            @endif
            @if ($assessment->module)
                <p class="mt-2 text-sm text-slate-300">Linked course: {{ $assessment->module->title }}</p>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gw-primary">Assessment rules</h2>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li>Passing score: <strong class="text-gw-primary">{{ $assessment->passing_score }}%</strong></li>
                    <li>Questions: <strong class="text-gw-primary">{{ $assessment->questions()->count() }}</strong></li>
                    @if ($maxAttempts)
                        <li>Attempts allowed: <strong class="text-gw-primary">{{ $maxAttempts }}</strong></li>
                    @else
                        <li>Attempts allowed: <strong class="text-gw-primary">Unlimited</strong></li>
                    @endif
                    @if (config('training-academy.assessments.require_course_completion') && $assessment->module)
                        <li>Complete the linked course before starting.</li>
                    @endif
                </ul>

                @if ($stats['latest_attempt'])
                    <div class="mt-6 rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-gw-primary">Latest attempt</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Score {{ $stats['latest_attempt']->score }}%
                            · {{ $stats['latest_attempt']->passed ? 'Passed' : 'Not passed' }}
                            · {{ $stats['latest_attempt']->completed_at?->format('M j, Y g:i A') }}
                        </p>
                        <a href="{{ route('assessments.attempts.show', [$assessment, $stats['latest_attempt']]) }}" class="mt-3 inline-flex text-sm font-semibold text-gw-primary underline">
                            Review results
                        </a>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your status</p>
                    <p class="mt-2 text-2xl font-bold {{ $stats['passed'] ? 'text-emerald-700' : 'text-gw-primary' }}">
                        {{ $stats['passed'] ? 'Passed' : ($stats['attempts_count'] > 0 ? 'Keep trying' : 'Ready') }}
                    </p>
                    @if ($stats['best_score'] !== null)
                        <p class="mt-1 text-sm text-slate-600">Best score: {{ $stats['best_score'] }}%</p>
                    @endif
                    <p class="mt-1 text-sm text-slate-600">{{ $stats['attempts_count'] }} attempt(s) used</p>
                </div>

                @if ($canTake)
                    <a href="{{ route('assessments.take', $assessment) }}" class="flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-3 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        {{ $stats['attempts_count'] > 0 ? 'Retake assessment' : 'Start assessment' }}
                    </a>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                        @switch($lockReason)
                            @case('passed')
                                You have already passed this assessment.
                                @break
                            @case('max_attempts')
                                You have used all available attempts.
                                @break
                            @case('course_incomplete')
                                Complete all lessons in the linked course before taking this assessment.
                                @if ($assessment->module)
                                    <a href="{{ route('training.courses.show', $assessment->module) }}" class="mt-3 block font-semibold underline">Go to course</a>
                                @endif
                                @break
                            @default
                                This assessment is not available right now.
                        @endswitch
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
