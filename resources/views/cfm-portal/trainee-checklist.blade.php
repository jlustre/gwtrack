<x-app-layout>
    @php
        $assignment = $payload['assignment'];
        $trainee = $payload['trainee'];
        $stats = $payload['stats'];
        $phases = $payload['phases'];
    @endphp

    <section class="cfm-portal-page space-y-6">
        <div class="overflow-hidden rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-5 text-white shadow-lg sm:p-6">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start lg:gap-8">
                <div class="min-w-0">
                    <a href="{{ route('cfm.portal') }}" class="inline-flex items-center gap-1 text-sm text-slate-300 transition hover:text-gw-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                        Back to CFM Portal
                    </a>
                    <p class="mt-3 text-sm font-semibold uppercase tracking-wide text-gw-accent">Trainee Mentoring Checklist</p>
                    <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">{{ $trainee->name }}</h1>
                    <p class="mt-2 max-w-none text-sm leading-snug text-slate-200 lg:pr-4 xl:max-w-3xl">
                        Track mentoring progress across assignment, orientation, licensing, compliance, training, FAP, and graduation.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3 lg:min-w-[20rem]">
                    <div class="rounded-lg border border-white/20 bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Rank</div>
                        <div class="mt-1 font-semibold">{{ $trainee->rank?->name ?? 'Associate' }}</div>
                    </div>
                    <div class="rounded-lg border border-white/20 bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Assignment</div>
                        <div class="mt-1 font-semibold">{{ ucfirst($assignment->status) }}</div>
                    </div>
                    <div class="col-span-2 rounded-lg border border-white/20 bg-white/10 px-4 py-3 sm:col-span-1">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Location</div>
                        <div class="mt-1 font-semibold">{{ $trainee->profile?->city ?: '—' }}{{ $trainee->profile?->province ? ', '.$trainee->profile->province : '' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('profile_feedback'))
            <div class="rounded-lg border px-4 py-3 text-sm font-medium {{ session('profile_feedback.type') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}">
                {{ session('profile_feedback.message') }}
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <a
                href="#cfm-checklist-phases"
                class="group rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm transition hover:border-gw-accent hover:shadow-md lg:col-span-1"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gw-accent-dark">Overall Progress</p>
                    <span class="rounded-full bg-gw-accent/20 px-2.5 py-1 text-xs font-bold text-gw-accent-dark">{{ $stats['percent'] }}%</span>
                </div>
                <div class="mt-3 text-3xl font-semibold text-gw-primary">{{ $stats['completed'] }}/{{ $stats['total'] }}</div>
                <p class="mt-1 text-sm text-slate-600">Items completed</p>
                <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-gw-accent transition-all" style="width: {{ $stats['percent'] }}%"></div>
                </div>
                <p class="mt-3 text-xs font-semibold text-gw-accent-dark opacity-0 transition group-hover:opacity-100">Jump to checklist ↓</p>
            </a>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-semibold text-gw-primary">Phase Overview</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($phases as $phase)
                        <a
                            href="#cfm-checklist-phase-{{ $phase['phase_number'] }}"
                            class="group rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 transition hover:border-gw-accent hover:bg-gw-accent-soft"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-500">Phase {{ $phase['phase_number'] }}</span>
                                <span class="text-xs font-bold text-gw-accent-dark">{{ $phase['percent'] }}%</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-gw-primary">{{ $phase['phase_title'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $phase['completed'] }}/{{ $phase['total'] }} complete</p>
                            <p class="mt-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-gw-accent-dark opacity-0 transition group-hover:opacity-100">View phase ↓</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="cfm-checklist-phases" class="scroll-mt-6 space-y-4">
            @php($checklistRow = 0)
            @foreach ($phases as $phase)
                <div
                    id="cfm-checklist-phase-{{ $phase['phase_number'] }}"
                    x-data="{ open: {{ $phase['percent'] < 100 ? 'true' : 'false' }} }"
                    x-on:cfm-checklist-open.window="if ($el.id === window.location.hash.slice(1)) open = true"
                    class="scroll-mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        class="flex w-full items-start justify-between gap-4 px-6 py-5 text-left transition hover:bg-slate-50"
                        :aria-expanded="open.toString()"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-gw-accent-soft px-2.5 py-0.5 text-xs font-bold text-gw-accent-dark">Phase {{ $phase['phase_number'] }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $phase['completed'] }}/{{ $phase['total'] }}</span>
                            </div>
                            <h2 class="mt-2 text-lg font-semibold text-gw-primary">{{ $phase['phase_title'] }}</h2>
                            @if ($phase['phase_target'])
                                <p class="mt-1 text-sm text-slate-500">Target: {{ $phase['phase_target'] }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <div class="hidden w-28 sm:block">
                                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full bg-gw-accent" style="width: {{ $phase['percent'] }}%"></div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak class="border-t border-slate-200">
                        @foreach ($phase['sections'] as $section)
                            <div class="border-b border-slate-200 last:border-b-0">
                                <div class="bg-slate-50 px-6 py-3">
                                    <h3 class="text-sm font-semibold text-gw-accent-dark">{{ $section['title'] }}</h3>
                                </div>
                                <div>
                                    @foreach ($section['items'] as $item)
                                        @php($checklistRow++)
                                        <div @class([
                                            'gw-checklist-item grid gap-4 lg:grid-cols-[auto_1fr_auto] lg:items-start',
                                            'gw-checklist-item--odd' => $checklistRow % 2 === 1,
                                            'gw-checklist-item--even' => $checklistRow % 2 === 0,
                                        ])>
                                            <form
                                                method="POST"
                                                action="{{ route('cfm.portal.trainees.checklist.update', [$assignment, $item['id']]) }}"
                                                class="pt-0.5"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="completed" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="completed"
                                                    value="1"
                                                    class="h-5 w-5 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                                                    @checked($item['is_completed'])
                                                    onchange="this.form.submit()"
                                                    aria-label="Mark {{ $item['title'] }} as complete"
                                                >
                                            </form>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-medium {{ $item['is_completed'] ? 'text-slate-400 line-through' : 'text-gw-primary' }}">
                                                        {{ $item['title'] }}
                                                    </p>
                                                    @if (! empty($item['description']))
                                                        <x-checklist-description-help :text="$item['description']" />
                                                    @endif
                                                    @if ($item['is_required'])
                                                        <span class="rounded-full bg-gw-accent-soft px-2 py-0.5 text-xs font-bold text-gw-accent-dark">Required</span>
                                                    @endif
                                                    @if (! empty($item['nth_day']))
                                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">Day {{ $item['nth_day'] }}</span>
                                                    @endif
                                                    @if (! empty($item['expected_due_date']))
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Due {{ $item['expected_due_date']->format('M j, Y') }}</span>
                                                    @endif
                                                </div>
                                                @if ($item['notes'])
                                                    <p class="mt-1 text-sm text-slate-500">{{ $item['notes'] }}</p>
                                                @endif
                                            </div>

                                            <div class="lg:text-right">
                                                @if (! empty($item['action_url']))
                                                    <a href="{{ $item['action_url'] }}"
                                                        class="mb-2 inline-flex items-center justify-center rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                                                        {{ $item['action_label'] ?? 'Open' }}
                                                    </a>
                                                @endif
                                                @if ($item['is_completed'])
                                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Completed</span>
                                                    @if ($item['completed_at'])
                                                        <div class="mt-1 text-xs text-slate-500">{{ $item['completed_at']->format('M j, Y') }}</div>
                                                    @endif
                                                @else
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Not started</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openPhaseFromHash = () => {
                const hash = window.location.hash;
                if (! hash.startsWith('#cfm-checklist-phase-')) {
                    return;
                }

                const target = document.querySelector(hash);
                if (! target) {
                    return;
                }

                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                window.dispatchEvent(new CustomEvent('cfm-checklist-open'));
            };

            openPhaseFromHash();
            window.addEventListener('hashchange', openPhaseFromHash);
        });
    </script>
</x-app-layout>
