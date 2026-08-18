<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">AI Coach</h1>
                <p class="mt-2 text-sm text-slate-200">Read-only recommendations based on your pipeline rules. No follow-ups are created until you act.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full border border-gw-accent bg-gw-accent/20 px-3 py-1 text-sm font-semibold text-gw-accent">{{ $totalCount }} suggestions</span>
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
            </div>
        </div>
    </div>

    @php
        $priorityStyles = [
            'high' => 'border-red-300 bg-red-50',
            'medium' => 'border-amber-300 bg-amber-50',
            'low' => 'border-slate-300 bg-slate-50',
        ];
        $actionLabels = [
            'log_call' => 'Log Call',
            'log_text' => 'Log Text',
            'schedule_followup' => 'Schedule',
            'escalate' => 'Escalate',
            'act_today' => 'Act Today',
        ];
    @endphp

    <section class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Phase 7 Intelligence</p>
                <h2 class="mt-1 text-lg font-semibold text-gw-primary">Lead Scoring & Forecasting</h2>
                <p class="mt-1 text-sm text-slate-600">Explainable scores based on funnel stage, activity, calendar commitments, and follow-up risk.</p>
            </div>
        </div>
        <div class="mt-5 grid gap-3 lg:grid-cols-4">
            @forelse ($scoredProspects as $score)
                <a href="{{ route('team.prospects.records.show', $score['prospect_id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4 transition hover:border-gw-accent/60">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gw-primary">{{ $score['prospect_name'] }}</p>
                            <p class="mt-1 text-xs capitalize text-slate-500">{{ $score['confidence'] }} confidence</p>
                        </div>
                        <span class="rounded-full bg-gw-accent-muted px-2 py-1 text-xs font-bold text-gw-accent-dark">{{ $score['lead_score'] }}</span>
                    </div>
                    @if ($score['recruiting_probability'] > 0)
                        <p class="mt-3 text-xs font-semibold text-slate-600">Recruiting probability: {{ $score['recruiting_probability'] }}%</p>
                    @endif
                    @if (! empty($score['reasons']))
                        <p class="mt-2 text-xs text-slate-500">{{ $score['reasons'][0] }}</p>
                    @endif
                </a>
            @empty
                <p class="text-sm text-slate-500">No active prospects available for scoring yet.</p>
            @endforelse
        </div>
    </section>

    @forelse ($groupedRecommendations as $priority => $items)
        <section class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold capitalize text-gw-primary">{{ $priority }} priority</h2>
            <div class="mt-4 space-y-3">
                @foreach ($items as $item)
                    <article class="rounded-lg border p-4 {{ $priorityStyles[$priority] ?? $priorityStyles['medium'] }}">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <a href="{{ route('team.prospects.records.show', $item['prospect_id']) }}" class="font-semibold text-gw-primary hover:text-gw-accent-dark">
                                    {{ $item['prospect_name'] }}
                                </a>
                                <p class="mt-1 text-sm text-slate-600">{{ $item['message'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">Lead score: {{ $item['score'] }}/100</p>
                                @if ($item['suggested_due'])
                                    <p class="mt-1 text-xs text-slate-500">Suggested by {{ \Illuminate\Support\Carbon::parse($item['suggested_due'])->format('M j, g:i A') }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if (in_array($item['suggested_action'], ['log_call', 'act_today', 'schedule_followup', 'escalate'], true))
                                    <button
                                        type="button"
                                        wire:click="$dispatch('open-log-activity-modal', { prospectId: '{{ $item['prospect_id'] }}', activityType: 'phone_call' })"
                                        class="rounded-lg border border-gw-accent bg-gw-accent-muted px-3 py-1.5 text-xs font-semibold text-gw-primary"
                                    >
                                        {{ $actionLabels[$item['suggested_action']] ?? 'Log Call' }}
                                    </button>
                                @endif
                                @if ($item['suggested_action'] === 'schedule_followup')
                                    <a href="{{ route('team.prospects.appointments') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                                        Appointments
                                    </a>
                                @endif
                                <a href="{{ route('team.prospects.records.show', $item['prospect_id']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-6 text-sm text-emerald-800">
            No recommendations right now. Your pipeline looks healthy.
        </div>
    @endforelse

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Stalled Prospects</h2>
            <p class="mt-1 text-sm text-slate-600">Applications or registrations needing escalation.</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($stalledProspects as $item)
                    <li>
                        <a href="{{ route('team.prospects.records.show', $item['prospect_id']) }}" class="font-semibold text-gw-accent-dark hover:text-gw-primary">
                            {{ $item['prospect_name'] }}
                        </a>
                        <span class="text-slate-500"> · {{ $item['stage'] }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">No stalled prospects detected.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">High-Value Opportunities</h2>
            <p class="mt-1 text-sm text-slate-600">Hot prospects with recent activity or high interest scores.</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($highValueOpportunities as $item)
                    <li>
                        <a href="{{ route('team.prospects.records.show', $item['prospect_id']) }}" class="font-semibold text-gw-accent-dark hover:text-gw-primary">
                            {{ $item['prospect_name'] }}
                        </a>
                        <span class="text-slate-500">
                            · {{ str($item['interest_level'])->title() }}
                            @if ($item['interest_score']) ({{ $item['interest_score'] }}/10) @endif
                        </span>
                    </li>
                @empty
                    <li class="text-slate-500">No high-value opportunities flagged.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <livewire:prospects.log-activity-modal />
    <livewire:prospects.log-communication-modal />
</div>
