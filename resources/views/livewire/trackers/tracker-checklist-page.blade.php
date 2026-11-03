@php
    $statusText = $statusMessage ? str($statusMessage)->replace('-', ' ')->title() : null;
@endphp

<div class="space-y-6">
    @if (! $started)
        @php
            $ns = $notStarted ?? [];
        @endphp
        <section class="mx-auto max-w-3xl space-y-6 py-8">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
                <div class="border-b border-slate-200 bg-gw-primary px-6 py-8 text-white">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $ns['checklistTypeName'] }}</p>
                    <h1 class="mt-2 text-2xl font-semibold">Not started yet</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                        @if ($ns['canStartNow'] ?? false)
                            This checklist has not been started yet. Choose your Day 1 below to open the tracker and begin working through your checklist items.
                        @else
                            This checklist is not available to start yet. Complete the required prerequisites below, then return here to begin.
                        @endif
                    </p>
                </div>
                <div class="space-y-4 px-6 py-6 text-sm text-slate-600">
                    @if ($ns['canStartNow'] ?? false)
                        <form wire:submit="startChecklist" class="rounded-md border border-gw-accent/40 bg-gw-accent-soft p-4">
                            <p class="font-semibold text-gw-primary">Start checklist schedule</p>
                            <p class="mt-1 text-slate-600">Day 1 is the date you choose below. Your expected due dates will be calculated from this start date.</p>
                            @if (! empty($ns['unmetPrerequisites']))
                                <p class="mt-2 text-xs text-amber-800">
                                    Note: typical prerequisites ({{ implode(', ', $ns['unmetPrerequisites']) }}) are not complete. Admins may still start this checklist for testing.
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('startedAt')" class="mt-3" />
                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                <label class="block flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Day 1 start date</span>
                                    <input
                                        type="date"
                                        wire:model.live="startedAt"
                                        required
                                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                    >
                                </label>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="startChecklist">Start checklist</span>
                                    <span wire:loading wire:target="startChecklist">Starting…</span>
                                </button>
                            </div>

                            @if (! empty($plannedMaxCompleteDays) && $plannedCompletionDueDate)
                                <div class="mt-3 flex flex-col gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                                    <span>Days to complete: {{ $plannedMaxCompleteDays }} day{{ $plannedMaxCompleteDays === 1 ? '' : 's' }}</span>
                                    <span>Expected completion date: <span class="tabular-nums">{{ $plannedCompletionDueDate->format('M j, Y') }}</span></span>
                                </div>
                            @endif
                        </form>
                    @else
                        @if (! empty($ns['unmetPrerequisites']))
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                                <p class="font-semibold">Prerequisites required</p>
                                <p class="mt-1">Complete these checklist types first: {{ implode(', ', $ns['unmetPrerequisites']) }}.</p>
                            </div>
                        @endif
                        @if ($ns['requiresLicense'] ?? false)
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                                <p class="font-semibold">Life license required</p>
                                <p class="mt-1">Contracting can start only after an active life insurance license is on the associate profile.</p>
                                <a href="{{ route('profile.edit', ['tab' => 'licenses']) }}" class="mt-2 inline-flex text-sm font-semibold text-gw-primary underline decoration-gw-accent underline-offset-2">
                                    Add license details
                                </a>
                            </div>
                        @endif
                        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="font-semibold text-gw-primary">Need help?</p>
                            <p class="mt-1">Contact your sponsor, Agency Owner, or CFM if you believe this checklist should already be active.</p>
                        </div>
                    @endif

                    @if (($tracker['key'] ?? null) === 'licensing')
                        <div class="rounded-md border border-slate-200 bg-white p-4">
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-2 border-slate-400 text-gw-accent focus:ring-gw-accent"
                                    onchange="if (this.checked) { window.location.href = '{{ route('profile.edit', ['tab' => 'licenses', 'already_licensed' => 1]) }}'; }"
                                >
                                <div>
                                    <p class="font-semibold text-gw-primary">I'm already licensed</p>
                                    <p class="mt-1 text-slate-600">Check this box to skip the licensing schedule and go straight to your profile, where you can add your license jurisdiction, number, and expiration date.</p>
                                </div>
                            </label>
                        </div>
                    @endif
                    @if (($tracker['key'] ?? null) === 'cfm-training')
                        @livewire(\App\Livewire\Cfm\CertificationApplication::class)
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="space-y-6">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
                <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $tracker['eyebrow'] }}</p>
                            <h1 class="mt-2 text-2xl font-semibold">{{ $tracker['title'] }}</h1>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $tracker['description'] }}</p>
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
                    'tracker' => $tracker,
                ])
            </div>

            @if ($statusText)
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" wire:key="status-{{ md5($statusMessage) }}">
                    {{ $statusText }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
                <div class="flex flex-col gap-2 border-b border-slate-200 bg-white/70 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gw-primary">{{ $tracker['checklistTitle'] }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $tracker['checklistDescription'] }}</p>
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
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $steps->count() }} {{ $tracker['itemCountLabel'] }}</span>
                </div>

                <div wire:loading.class="opacity-60" wire:target="toggleStep">
                    @forelse ($steps as $step)
                        <div
                            wire:key="tracker-step-{{ $step->id }}-{{ $step->status }}"
                            x-data="{ expanded: false }"
                            @class([
                                'gw-checklist-item',
                                'gw-checklist-item--odd' => $loop->odd,
                                'gw-checklist-item--even' => $loop->even,
                            ])
                        >
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="flex h-5 shrink-0 items-center">
                                                @if ($step->is_completed)
                                                    <input
                                                        type="checkbox"
                                                        wire:key="tracker-check-{{ $step->id }}-completed"
                                                        class="h-4 w-4 rounded border-2 border-slate-500 text-gw-accent focus:ring-gw-accent checked:border-gw-accent disabled:cursor-not-allowed disabled:opacity-70"
                                                        checked
                                                        disabled
                                                        aria-label="{{ $step->title }} {{ $step->requires_confirmation ? 'confirmed' : 'auto-approved' }}"
                                                        title="{{ $step->requires_confirmation ? 'Confirmed — this item can no longer be changed' : 'Auto-approved — this item can no longer be changed' }}"
                                                    >
                                                @else
                                                    <input
                                                        type="checkbox"
                                                        wire:key="tracker-check-{{ $step->id }}-{{ $step->status }}"
                                                        class="h-4 w-4 rounded border-2 border-slate-500 text-gw-accent focus:ring-gw-accent checked:border-gw-accent"
                                                        @checked($step->is_pending)
                                                        wire:loading.attr="disabled"
                                                        wire:target="toggleStep"
                                                        wire:click="toggleStep({{ $step->id }}, {{ $step->is_pending ? 'false' : 'true' }})"
                                                        aria-label="{{ $step->requires_confirmation ? 'Submit '.$step->title.' for confirmation' : 'Mark '.$step->title.' complete (auto-approved)' }}"
                                                    >
                                                @endif
                                            </div>
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
                                                @if (! $step->requires_confirmation)
                                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700" title="Completing this item is approved automatically — no CFM confirmation required">Auto-approved</span>
                                                @else
                                                    <span class="rounded-full bg-amber-50/80 px-2 py-0.5 text-xs font-bold text-amber-800" title="A confirmer such as the CFM must approve this item">Confirmation needed</span>
                                                @endif
                                                @if ($step->is_required)
                                                    <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-xs font-bold text-gw-accent-dark">Required</span>
                                                @else
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">Optional</span>
                                                @endif
                                                @if (! empty($step->group_label))
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $step->group_label }}</span>
                                                @endif
                                                @include('checklists.partials.nth-day-schedule', ['step' => $step])
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            x-on:click="expanded = ! expanded"
                                            class="gw-icon-btn cursor-pointer"
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

                                <div class="hidden shrink-0 self-center lg:block lg:text-right">
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
                            <h3 class="text-lg font-semibold text-gw-primary">{{ $tracker['emptyTitle'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $tracker['emptyDescription'] }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
