@php
    $pipelineSummaryGrouped = $pipelineSummaryGrouped ?? collect();
    $pipelineFunnelId = $pipelineFunnelId ?? null;
    $groupHeadingClasses = [
        'sky' => 'text-sky-700',
        'indigo' => 'text-indigo-700',
        'violet' => 'text-violet-700',
        'amber' => 'text-amber-700',
        'emerald' => 'text-emerald-700',
        'teal' => 'text-teal-700',
        'rose' => 'text-rose-700',
        'slate' => 'text-slate-600',
    ];
    $stageProspectsUrl = route('team.prospects.pipeline-stage-prospects', ['stage' => '__STAGE__', 'funnel_id' => $pipelineFunnelId]);
@endphp

<div class="grid gap-6 xl:grid-cols-2">
    <section
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        x-init="initStageProspectsModal(@js($stageProspectsUrl))"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Pipeline Summary</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $pipelineSummaryFunnel ?? 'Pipeline' }} lifecycle
                    · {{ $pipelineSummary->sum('prospect_count') }} active prospects across stages
                </p>
            </div>
            <a href="{{ route('team.prospects.pipeline') }}" class="rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-3 py-1.5 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                Open sales board
            </a>
        </div>

        <div class="mt-5 space-y-5">
            @forelse ($pipelineSummaryGrouped as $group)
                <div>
                    <h3 @class([
                        'text-base font-bold uppercase tracking-wide',
                        $groupHeadingClasses[$group['color']] ?? $groupHeadingClasses['slate'],
                    ])>
                        {{ $group['label'] }}
                    </h3>
                    <div class="mt-2 space-y-2.5">
                        @foreach ($group['stages'] as $stage)
                            @php($count = (int) $stage->prospect_count)
                            <div>
                                <div class="flex items-center justify-between gap-2 text-sm">
                                    <span class="min-w-0 flex-1 font-medium text-gw-primary">{{ $stage->label }}</span>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span @class(['font-semibold', 'text-slate-400' => $count === 0, 'text-slate-600' => $count > 0])>{{ $count }}</span>
                                        <button
                                            type="button"
                                            title="View prospects in this stage"
                                            aria-label="View prospects in {{ $stage->label }}"
                                            class="inline-flex rounded border border-slate-200 bg-white p-1 text-slate-500 transition hover:border-gw-accent/50 hover:bg-gw-accent-soft hover:text-gw-accent-dark"
                                            x-on:click="openStageProspects({{ $stage->id }}, @js($stage->label))"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No pipeline stages configured yet.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Follow-Up Center</h2>
                <p class="mt-1 text-sm text-slate-600">Due today or overdue</p>
            </div>
            <a href="{{ route('team.prospects.follow-ups') }}" class="rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-3 py-1.5 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                View all
            </a>
        </div>
        <ul class="mt-5 divide-y divide-slate-100">
            @forelse ($followUpsDueToday as $followUp)
                <li class="flex items-start justify-between gap-3 py-3 first:pt-0">
                    <div class="min-w-0">
                        @if ($followUp->prospect_id ?? null)
                            <a href="{{ route('team.prospects.records.show', $followUp->prospect_id) }}" class="font-semibold text-gw-primary hover:text-gw-accent-dark">
                                {{ trim($followUp->first_name.' '.$followUp->last_name) }}
                            </a>
                        @else
                            <p class="font-semibold text-gw-primary">{{ trim($followUp->first_name.' '.$followUp->last_name) }}</p>
                        @endif
                        <p class="mt-1 text-sm text-slate-600">{{ $followUp->followup_type ?? 'Follow up' }} · {{ \Illuminate\Support\Carbon::parse($followUp->due_at)->format('M j, g:i A') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $priorityClasses[$followUp->priority] ?? $priorityClasses['medium'] }}">
                        {{ $followUp->priority }}
                    </span>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">No follow-ups due right now.</li>
            @endforelse
        </ul>
    </section>
</div>
