<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border {{ $attempt->passed ? 'border-emerald-300' : 'border-amber-300' }} bg-gradient-to-br {{ $attempt->passed ? 'from-emerald-900 via-emerald-800 to-emerald-900' : 'from-gw-primary via-gw-primary-hover to-gw-primary' }} p-6 text-white shadow-lg">
            <a href="{{ route('assessments.show', $assessment) }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; {{ $assessment->title }}</a>
            <h1 class="mt-2 text-3xl font-semibold">{{ $attempt->passed ? 'Assessment passed' : 'Assessment complete' }}</h1>
            <p class="mt-2 text-sm text-slate-200">
                Score {{ $attempt->score }}% · Required {{ $assessment->passing_score }}%
                · {{ $attempt->completed_at?->format('M j, Y g:i A') }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Question review</h2>
                    <div class="mt-4 space-y-4">
                        @foreach ($breakdown as $index => $row)
                            <div class="rounded-lg border border-slate-100 px-4 py-4 {{ $row['is_correct'] ? 'bg-emerald-50/70' : 'bg-red-50/70' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-gw-primary">{{ $index + 1 }}. {{ $row['question'] }}</p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase {{ $row['is_correct'] ? 'bg-emerald-200 text-emerald-900' : 'bg-red-200 text-red-900' }}">
                                        {{ $row['is_correct'] ? 'Correct' : 'Incorrect' }}
                                    </span>
                                </div>
                                @if (($row['type'] ?? '') === 'short_answer')
                                    <p class="mt-2 text-sm text-slate-600">Your answer: {{ $row['response']['text'] ?? '—' }}</p>
                                @else
                                    @php
                                        $selected = collect($row['answers'] ?? [])->firstWhere('id', (int) ($row['response']['answer_id'] ?? 0));
                                    @endphp
                                    <p class="mt-2 text-sm text-slate-600">Your answer: {{ $selected?->answer ?? '—' }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Result</p>
                    <p class="mt-2 text-3xl font-bold {{ $attempt->passed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $attempt->score }}%</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $attempt->passed ? 'You met the passing score.' : 'You did not meet the passing score.' }}</p>
                </div>

                @if ($assessment->module)
                    <a href="{{ route('training.courses.show', $assessment->module) }}" class="flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                        Back to course
                    </a>
                @endif

                @if ($canRetake)
                    <a href="{{ route('assessments.take', $assessment) }}" class="flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        Retake assessment
                    </a>
                @endif

                @if ($certificationRecord)
                    <div class="rounded-xl border {{ $certificationRecord->isIssued() ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-4">
                        <p class="text-sm font-semibold {{ $certificationRecord->isIssued() ? 'text-emerald-900' : 'text-amber-900' }}">
                            {{ $certificationRecord->certification?->name }}
                        </p>
                        <p class="mt-1 text-xs {{ $certificationRecord->isIssued() ? 'text-emerald-800' : 'text-amber-800' }}">
                            {{ $certificationRecord->isIssued() ? 'Certification issued.' : 'Certification pending mentor approval.' }}
                        </p>
                        <a href="{{ route('training.certifications.show', $certificationRecord) }}" class="mt-2 inline-flex text-xs font-semibold underline">
                            View certification
                        </a>
                    </div>
                @endif

                <a href="{{ route('assessments.index') }}" class="flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                    All assessments
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
