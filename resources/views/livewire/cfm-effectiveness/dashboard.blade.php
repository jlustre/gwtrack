<div class="space-y-6">
    @if ($canSelectCfm && $cfmOptions->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white/90 p-4 shadow-sm">
            <label for="cfm-effectiveness-select" class="text-sm font-semibold text-gw-primary">View CFM</label>
            <select
                id="cfm-effectiveness-select"
                wire:model.live="cfmId"
                class="mt-2 block w-full max-w-md rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                @foreach ($cfmOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-gw-accent/30 bg-white/90 p-6 shadow-sm backdrop-blur-sm xl:col-span-1">
            <div class="flex items-start gap-4">
                @if ($data['cfm']['photo_url'])
                    <img src="{{ $data['cfm']['photo_url'] }}" alt="" class="h-20 w-20 rounded-full border-2 border-gw-accent/40 object-cover">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-gw-accent/40 bg-gw-primary text-xl font-bold text-gw-accent">
                        {{ substr($data['cfm']['name'], 0, 1) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-semibold text-gw-primary">{{ $data['cfm']['name'] }}</h2>
                    <p class="text-sm text-slate-600">{{ $data['cfm']['rank'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $data['cfm']['years_experience'] }} years experience</p>
                </div>
            </div>
            <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Active Trainees</dt><dd class="font-semibold text-gw-primary">{{ $data['cfm']['active_trainees'] }}</dd></div>
                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Graduated</dt><dd class="font-semibold text-gw-primary">{{ $data['cfm']['graduated_trainees'] }}</dd></div>
                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Licensed</dt><dd class="font-semibold text-gw-primary">{{ $data['cfm']['licensed_trainees'] }}</dd></div>
                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Promoted</dt><dd class="font-semibold text-gw-primary">{{ $data['cfm']['promoted_trainees'] }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-accent-soft to-white p-6 shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">CFM Effectiveness Score</p>
                    <p class="mt-1 text-5xl font-bold text-gw-primary">{{ number_format($data['effectiveness_score'], 0) }}<span class="text-2xl text-slate-500">/100</span></p>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center text-sm">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <p class="text-slate-500">Objective</p>
                        <p class="font-semibold text-gw-primary">{{ number_format($data['score_breakdown']['objective'], 0) }}%</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <p class="text-slate-500">Feedback</p>
                        <p class="font-semibold text-gw-primary">{{ number_format($data['score_breakdown']['feedback'], 0) }}%</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <p class="text-slate-500">AO Rating</p>
                        <p class="font-semibold text-gw-primary">{{ number_format($data['score_breakdown']['ao'], 0) }}%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Retention', 'value' => ($data['objective_metrics']['retention_rate']['value'] ?? 0).'%', 'theme' => 'emerald'],
            ['label' => 'FAP Completion', 'value' => ($data['objective_metrics']['fap_completion_rate']['value'] ?? 0).'%', 'theme' => 'gold'],
            ['label' => 'Licensing', 'value' => ($data['objective_metrics']['licensing_completion_rate']['value'] ?? 0).'%', 'theme' => 'cyan'],
            ['label' => 'Response Time', 'value' => $data['objective_metrics']['responsiveness_score']['meta']['band'] ?? '—', 'theme' => 'amber'],
            ['label' => 'Coaching Activity', 'value' => number_format($data['objective_metrics']['coaching_activity_score']['score'] ?? 0, 0), 'theme' => 'violet'],
            ['label' => 'Trainee Satisfaction', 'value' => filled($data['trainee_satisfaction'] ?? null) ? $data['trainee_satisfaction'].'%' : '—', 'theme' => 'navy'],
            ['label' => 'Open Coaching Items', 'value' => $data['open_coaching_items'], 'theme' => 'slate'],
            ['label' => 'Upcoming Reviews', 'value' => $data['upcoming_reviews'], 'theme' => 'red'],
        ] as $card)
            <x-tracker-stat-card :label="$card['label']" :value="$card['value']" :theme="$card['theme']" />
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm">
            <h3 class="text-lg font-semibold text-gw-primary">Objective Performance Metrics</h3>
            <div class="mt-4 space-y-4">
                @foreach ($data['objective_metrics'] as $metric)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gw-primary">{{ $metric['label'] }}</span>
                            <span class="text-slate-600">{{ number_format($metric['score'], 0) }}/100</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gw-accent" style="width: {{ min(100, $metric['score']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            @if (($data['feedback_summary']['review_count'] ?? 0) > 0)
                <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm">
                    <h3 class="text-lg font-semibold text-gw-primary">Trainee Feedback</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Based on {{ $data['feedback_summary']['review_count'] }} submitted review{{ $data['feedback_summary']['review_count'] === 1 ? '' : 's' }}.
                    </p>
                    @if (! empty($data['feedback_summary']['question_averages']))
                        <div class="mt-4 space-y-3">
                            @foreach ($data['feedback_summary']['question_averages'] as $question)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gw-primary">{{ $question['question'] }}</span>
                                        <span class="text-slate-600">{{ number_format($question['average'], 1) }}/5</span>
                                    </div>
                                    <div class="mt-1 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-gw-primary" style="width: {{ min(100, ($question['average'] / 5) * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($data['risks']->isNotEmpty())
                <div class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-red-900">Risk Alerts</h3>
                    <ul class="mt-3 space-y-2 text-sm text-red-800">
                        @foreach ($data['risks'] as $risk)
                            <li class="rounded-lg bg-white/70 px-3 py-2">{{ $risk->message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm">
                <h3 class="text-lg font-semibold text-gw-primary">Improvement Recommendations</h3>
                <ul class="mt-4 space-y-3">
                    @forelse ($data['recommendations'] as $rec)
                        <li class="rounded-lg border border-gw-accent/20 bg-gw-accent-soft/50 px-3 py-2 text-sm">
                            <p class="font-semibold text-gw-primary">{{ $rec['area'] }}</p>
                            <p class="mt-1 text-slate-600">{{ $rec['suggestion'] }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-600">Strong performance across tracked metrics. Keep reinforcing current coaching habits.</li>
                    @endforelse
                </ul>
            </div>

            @if ($data['badges']->isNotEmpty())
                <div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-accent-soft to-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gw-primary">Recent Recognition</h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($data['badges'] as $award)
                            <span class="rounded-full border border-gw-accent/40 bg-white px-3 py-1 text-xs font-semibold text-gw-accent-dark">{{ $award->badge?->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('livewire.cfm-effectiveness.partials.success-analytics', ['data' => $data])

    @if ($agency)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gw-primary">Agency Overview</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-tracker-stat-card label="Active CFMs" :value="$agency['cfm_count']" theme="navy" />
                <x-tracker-stat-card label="Avg Effectiveness" :value="number_format($agency['average_effectiveness'], 1)" theme="gold" />
                <x-tracker-stat-card label="At-Risk CFMs" :value="count($agency['at_risk_cfms'])" theme="red" />
            </div>
        </div>
    @endif
</div>
