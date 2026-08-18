@php
    $path = $detail['path'];
    $enrollment = $detail['enrollment'];
    $isEnrolled = $enrollment !== null;
@endphp

<div class="space-y-6">
    @if (session('path_status') === 'enrolled')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            You are enrolled in this learning path.
        </div>
    @endif

    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('training.paths.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Learning Paths</a>
                <h1 class="mt-2 text-3xl font-semibold">{{ $path->name }}</h1>
                @if ($path->description)
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $path->description }}</p>
                @endif
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-300">
                    {{ $path->modules->count() }} courses
                    @if ($path->audience)
                        · {{ str($path->audience)->replace('_', ' ')->title() }}
                    @endif
                    · {{ str($detail['status'])->replace('_', ' ')->title() }}
                </p>
            </div>
            <div class="min-w-[12rem] rounded-xl border border-white/20 bg-white/10 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Path progress</p>
                <p class="mt-1 text-3xl font-bold text-gw-accent">{{ $detail['progress_percent'] }}%</p>
                <div class="mt-3 h-2 rounded-full bg-white/20">
                    <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $detail['progress_percent'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <h2 class="text-lg font-semibold text-gw-primary">Path courses</h2>
            <p class="mt-1 text-xs text-slate-500">Complete courses in order to finish this learning path.</p>

            <ol class="mt-5 space-y-3">
                @foreach ($detail['module_rows'] as $index => $row)
                    @php
                        $module = $row['module'];
                    @endphp
                    <li class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">{{ $index + 1 }}</span>
                                    <a href="{{ route('training.courses.show', $module) }}" class="font-semibold text-gw-primary transition hover:text-gw-accent">{{ $module->title }}</a>
                                </div>
                                <p class="mt-1 pl-8 text-xs text-slate-500">
                                    {{ $module->category?->name }}
                                    · {{ str($module->course_type ?? 'video')->replace('_', ' ')->title() }}
                                    @if ($row['is_required']) · Required @endif
                                </p>
                                <div class="mt-3 pl-8">
                                    <div class="h-2 max-w-md rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $row['progress_percent'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="rounded-full bg-gw-primary px-2.5 py-1 text-[0.65rem] font-bold text-gw-accent">{{ $row['progress_percent'] }}%</span>
                                <p class="mt-1 text-[0.65rem] font-semibold uppercase text-slate-500">{{ str($row['status'])->replace('_', ' ')->title() }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="space-y-4">
            @if (! $isEnrolled)
                <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
                    <h3 class="font-semibold text-gw-primary">Start this path</h3>
                    <p class="mt-2 text-sm text-slate-600">Enroll to track your progress across all courses in this program.</p>
                    <button
                        type="button"
                        wire:click="enroll"
                        wire:loading.attr="disabled"
                        class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="enroll">Enroll in path</span>
                        <span wire:loading wire:target="enroll">Enrolling...</span>
                    </button>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gw-primary">Enrollment</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Started {{ $enrollment->started_at?->format('M j, Y') ?? 'recently' }}
                    </p>
                    @if ($detail['status'] === 'completed')
                        <p class="mt-2 text-sm font-semibold text-emerald-700">Path completed {{ $enrollment->completed_at?->format('M j, Y') }}.</p>
                    @else
                        @php
                            $nextModule = collect($detail['module_rows'])->first(fn (array $row) => $row['progress_percent'] < 100);
                        @endphp
                        @if ($nextModule)
                            <a href="{{ route('training.courses.show', $nextModule['module']) }}" class="mt-4 inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                                Continue next course
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
