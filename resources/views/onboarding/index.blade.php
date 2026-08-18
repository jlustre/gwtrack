<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Onboarding</p>
                        <h1 class="mt-2 text-2xl font-semibold">Start strong with a clear path forward</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                            Track your setup, sponsor alignment, licensing readiness, training start, and first field milestones.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-md bg-white/10 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-300">Sponsor</div>
                            <div class="mt-1 font-semibold">{{ $user->sponsor?->name ?? 'Not assigned' }}</div>
                        </div>
                        <div class="rounded-md bg-white/10 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-300">CFM</div>
                            <div class="mt-1 font-semibold">{{ $user->mentor?->name ?? 'Pending' }}</div>
                        </div>
                        <div class="rounded-md bg-white/10 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-300">Country</div>
                            <div class="mt-1 font-semibold">{{ $user->profile?->country ?? 'Global' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @include('trackers.partials.stats-dashboard', [
                'stats' => $stats,
                'completedLabel' => 'Items completed',
                'graphLabel' => 'Overall onboarding',
                'requiredLabel' => 'Required completion',
            ])
        </div>

        @if (session('status'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ str(session('status'))->replace('-', ' ')->title() }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 bg-white/70 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Onboarding Checklist</h2>
                    <p class="mt-1 text-sm text-slate-600">Check items off as you complete them. Country-specific items appear only when they apply to your profile.</p>
                    @if (! empty($typeStartDate))
                        <p class="mt-1 text-xs font-medium text-slate-500">Schedule starts {{ $typeStartDate->format('M j, Y') }} (Day 1).</p>
                    @endif
                    @if (! empty($typeMaxCompleteDays) && ! empty($typeCompletionDueDate))
                        @php
                            $targetDueOverdue = $stats['completed'] < $stats['total']
                                && \App\Support\ChecklistDueDisplay::isOverdue($typeCompletionDueDate);
                        @endphp
                        <p class="mt-1 text-xs font-medium text-slate-500">
                            Target completion by Day {{ $typeMaxCompleteDays }}
                            (<span @class([
                                'tabular-nums',
                                \App\Support\ChecklistDueDisplay::textClass($targetDueOverdue),
                            ])>{{ $typeCompletionDueDate->format('m/d/Y') }}</span>).
                        </p>
                    @endif
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $steps->count() }} applicable items</span>
            </div>

            <div>
                @forelse ($steps as $step)
                    <div x-data="{ expanded: false }" @class([
                        'gw-checklist-item',
                        'gw-checklist-item--odd' => $loop->odd,
                        'gw-checklist-item--even' => $loop->even,
                    ])>
                        <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-start gap-3">
                                    <form method="POST" action="{{ route('onboarding.update', $step) }}" class="flex h-5 shrink-0 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="completed" value="0">
                                        <input
                                            type="checkbox"
                                            name="completed"
                                            value="1"
                                            class="h-4 w-4 rounded border-2 border-slate-500 text-gw-accent focus:ring-gw-accent checked:border-gw-accent"
                                            @checked($step->is_completed || $step->is_pending)
                                            @disabled($step->is_completed)
                                            onchange="this.form.submit()"
                                            aria-label="Submit {{ $step->title }} for confirmation"
                                        >
                                    </form>
                                    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                        <h3 class="text-sm font-semibold leading-5 {{ $step->is_completed ? 'text-slate-500 line-through' : 'text-gw-primary' }}">{{ $step->title }}</h3>
                                        @if ($step->description)
                                            <x-checklist-description-help :text="$step->description" />
                                        @endif
                                        @if ($step->is_pending)
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700">Pending Confirmation</span>
                                        @endif
                                        @if ($step->is_rejected)
                                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">Rejected</span>
                                        @endif
                                        @if ($step->is_required)
                                            <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-xs font-bold text-gw-accent-dark">Required</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">Optional</span>
                                        @endif
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $step->country ?: 'Global' }}</span>
                                        @include('checklists.partials.nth-day-schedule', ['step' => $step])
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    x-on:click="expanded = ! expanded"
                                    class="gw-icon-btn"
                                    :aria-expanded="expanded.toString()"
                                    aria-label="Toggle item details"
                                    title="Toggle details"
                                >
                                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': expanded }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                            </div>

                            <div x-show="expanded" x-transition class="mt-2">
                                @if ($step->description)
                                    <p class="max-w-4xl text-sm leading-6 text-slate-600">{{ $step->description }}</p>
                                @endif

                                @if ($step->progress?->review_comments)
                                    <div class="mt-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <span class="font-semibold text-gw-primary">Review comments:</span>
                                        {{ $step->progress->review_comments }}
                                    </div>
                                @endif

                                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Responsible: {{ $step->responsible_parties ?: 'Self' }}</span>
                                    @if ($step->notified_parties)
                                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">Notify: {{ $step->notified_parties }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="hidden shrink-0 self-start lg:block lg:text-right">
                            <div x-show="expanded" x-transition>
                                @if ($step->is_completed)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Completed</span>
                                    @if ($step->progress?->completed_at)
                                        <div class="mt-2 text-xs text-slate-500">{{ \Carbon\Carbon::parse($step->progress->completed_at)->format('M j, Y') }}</div>
                                    @endif
                                @elseif ($step->is_pending)
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Pending</span>
                                    @if ($step->progress?->submitted_at)
                                        <div class="mt-2 text-xs text-slate-500">Submitted {{ \Carbon\Carbon::parse($step->progress->submitted_at)->format('M j, Y') }}</div>
                                    @endif
                                @elseif ($step->is_rejected)
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Rejected</span>
                                    @if ($step->progress?->reviewed_at)
                                        <div class="mt-2 text-xs text-slate-500">{{ \Carbon\Carbon::parse($step->progress->reviewed_at)->format('M j, Y') }}</div>
                                    @endif
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Not Started</span>
                                @endif
                            </div>
                        </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <h3 class="text-lg font-semibold text-gw-primary">No onboarding items available</h3>
                        <p class="mt-2 text-sm text-slate-600">Your checklist will appear when active onboarding items are assigned to your country or marked global.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
