@php($center = $sectionCenter)

<div class="space-y-4">
    @include('livewire.cfm.partials.centers.onboarding')

    @if ($center['mentoring'])
        <div class="rounded-xl border border-gw-primary/20 bg-gradient-to-br from-gw-primary to-[#102847] p-5 text-white shadow-lg sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">CFM Mentoring Checklist</p>
                    <h3 class="mt-1 text-lg font-semibold">Mentor responsibilities & evaluations</h3>
                    <p class="mt-2 text-sm text-slate-200">Approve mentor tasks, add feedback, and track weekly mentoring cadence.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $center['mentoring']['checklist_url'] }}" class="inline-flex rounded-lg border border-white/20 px-3 py-2 text-sm font-semibold text-white hover:bg-white/10">
                        Open full checklist
                    </a>
                    <button
                        type="button"
                        @click="openTraineeChecklistModal(@js($center['mentoring']['checklist_url']))"
                        class="inline-flex rounded-lg bg-gw-accent px-3 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover"
                    >
                        Quick view
                    </button>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['label' => 'Completion', 'value' => ($center['mentoring']['stats']['percent'] ?? 0).'%'],
                    ['label' => 'Completed', 'value' => ($center['mentoring']['stats']['completed'] ?? 0).'/'.($center['mentoring']['stats']['total'] ?? 0)],
                    ['label' => 'Remaining', 'value' => $center['mentoring']['stats']['remaining'] ?? 0],
                    ['label' => 'Phases', 'value' => count($center['mentoring']['phases'] ?? [])],
                ] as $card)
                    <div class="rounded-lg bg-white/10 px-4 py-3">
                        <p class="text-[0.65rem] uppercase tracking-wide text-slate-300">{{ $card['label'] }}</p>
                        <p class="mt-1 text-xl font-bold">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @foreach ($center['mentoring']['phases'] as $phase)
            <div wire:key="mentoring-phase-{{ $phase['phase_number'] }}" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Phase {{ $phase['phase_number'] }}</p>
                            <h4 class="text-base font-semibold text-gw-primary">{{ $phase['phase_title'] }}</h4>
                            @if ($phase['phase_target'])
                                <p class="mt-1 text-xs text-slate-500">{{ $phase['phase_target'] }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $phase['percent'] }}%</span>
                    </div>
                </div>

                @foreach ($phase['sections'] as $section)
                    <div class="border-b border-slate-100 last:border-b-0">
                        <div class="bg-slate-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section['title'] }}</div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($section['items'] as $item)
                                <li wire:key="mentoring-item-{{ $item['id'] }}" class="px-5 py-4">
                                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span @class([
                                                    'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                                    'bg-emerald-100 text-emerald-800' => $item['is_completed'],
                                                    'bg-slate-100 text-slate-600' => ! $item['is_completed'],
                                                ])>{{ $item['is_completed'] ? 'Complete' : 'Open' }}</span>
                                                @if ($item['is_required'])
                                                    <span class="text-[0.65rem] font-semibold uppercase text-gw-accent-dark">Required</span>
                                                @endif
                                            </div>
                                            <p class="mt-2 font-semibold text-gw-primary">{{ $item['title'] }}</p>
                                            @if ($item['description'])
                                                <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-500">
                                                @if ($item['expected_due_date'])
                                                    <span>Target {{ $item['expected_due_date'] }}</span>
                                                @endif
                                                @if ($item['completed_at'])
                                                    <span>Completed {{ $item['completed_at'] }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex w-full flex-col gap-2 xl:w-72">
                                            <textarea
                                                rows="2"
                                                placeholder="Mentor notes…"
                                                class="w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                                x-data="{ notes: @js($item['notes'] ?? '') }"
                                                x-model="notes"
                                            ></textarea>
                                            <div class="flex flex-wrap gap-2">
                                                @if ($item['is_completed'])
                                                    <button
                                                        type="button"
                                                        wire:click="toggleMentoringItem({{ $item['id'] }}, false)"
                                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Unapprove
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="toggleMentoringItem({{ $item['id'] }}, true)"
                                                        class="rounded-lg bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary hover:bg-gw-accent-hover"
                                                    >
                                                        Approve
                                                    </button>
                                                @endif
                                                <button
                                                    type="button"
                                                    x-on:click="$wire.saveMentoringItemNotes({{ $item['id'] }}, notes)"
                                                    class="rounded-lg border border-gw-accent/40 px-3 py-1.5 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-soft"
                                                >
                                                    Save notes
                                                </button>
                                                @if ($item['action_url'])
                                                    <a href="{{ $item['action_url'] }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:border-gw-accent">
                                                        {{ $item['action_label'] ?? 'Open' }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">
            No active mentor assignment — CFM mentoring checklist will appear after the trainee assignment is confirmed.
        </div>
    @endif
</div>
