<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Analytics &amp; Goals</h1>
                <p class="mt-2 text-sm text-slate-200">Pipeline conversion, activity trends, lead sources, and period goals.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
                <a href="{{ route('team.prospects.pipeline') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Sales Pipeline</a>
            </div>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Active Prospects', 'value' => $summary['total'], 'theme' => 'navy'],
                ['label' => 'New (30 days)', 'value' => $summary['new_30d'], 'theme' => 'cyan'],
                ['label' => 'Hot Prospects', 'value' => $summary['hot'], 'theme' => 'red'],
                ['label' => 'Follow-Ups Due', 'value' => $summary['followups_due'], 'theme' => 'amber'],
                ['label' => 'Upcoming Appts', 'value' => $summary['appointments_upcoming'], 'theme' => 'violet'],
                ['label' => 'Conversion Rate', 'value' => $summary['conversion_rate'].'%', 'theme' => 'gold'],
                ['label' => 'Insurance Pipeline', 'value' => $summary['insurance_count'], 'theme' => 'cyan'],
                ['label' => 'Recruiting Pipeline', 'value' => $summary['recruiting_count'], 'theme' => 'emerald'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    @if ($teamAggregates['visible'] ?? false)
        <div class="rounded-lg border border-gw-primary/20 bg-gradient-to-br from-gw-primary via-[#102A4C] to-gw-primary p-6 text-white shadow-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Team Aggregates</p>
                    <h2 class="mt-1 text-lg font-semibold">Downline performance (no PII)</h2>
                    <p class="mt-1 text-sm text-slate-300">{{ $teamAggregates['member_count'] }} team members in scope</p>
                </div>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Team Prospects', 'value' => $teamAggregates['total_prospects'], 'theme' => 'navy'],
                    ['label' => 'Team Hot', 'value' => $teamAggregates['hot_prospects'], 'theme' => 'red'],
                    ['label' => 'Follow-Ups Due', 'value' => $teamAggregates['followups_due'], 'theme' => 'amber'],
                    ['label' => 'Avg Conversion', 'value' => $teamAggregates['avg_conversion_rate'].'%', 'theme' => 'gold'],
                ] as $card)
                    <x-tracker-stat-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :theme="$card['theme']"
                    />
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Phase 5 Reporting</p>
                <h2 class="mt-1 text-lg font-semibold text-gw-primary">CRM Performance Reports</h2>
                <p class="mt-1 text-sm text-slate-600">Appointment outcomes, follow-up SLA, source conversion, and team rollups.</p>
            </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-4">
            @php($appointment = $performanceReport['appointment'])
            @foreach ([
                ['label' => 'Appointments', 'value' => $appointment['total'], 'detail' => $appointment['calendar_linked'].' calendar linked'],
                ['label' => 'Completed', 'value' => $appointment['completed'], 'detail' => $appointment['completion_rate'].'% completion'],
                ['label' => 'No-Shows', 'value' => $appointment['no_show'], 'detail' => $appointment['attendance_rate'].'% attendance'],
                ['label' => 'Cancelled', 'value' => $appointment['cancelled'], 'detail' => $appointment['cancellation_rate'].'% cancellation'],
            ] as $metric)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gw-primary">{{ $metric['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $metric['detail'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr_1.1fr]">
            @php($sla = $performanceReport['follow_up_sla'])
            <div class="rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-gw-primary">Follow-Up SLA</h3>
                <div class="mt-4 space-y-3">
                    @foreach ([
                        ['label' => 'On time', 'value' => $sla['on_time'], 'bar' => $sla['on_time_rate'], 'color' => 'bg-emerald-500'],
                        ['label' => 'Late', 'value' => $sla['late'], 'bar' => $sla['completed'] > 0 ? round(($sla['late'] / max(1, $sla['completed'])) * 100) : 0, 'color' => 'bg-amber-500'],
                        ['label' => 'Open overdue', 'value' => $sla['overdue_open'], 'bar' => $sla['total'] > 0 ? round(($sla['overdue_open'] / max(1, $sla['total'])) * 100) : 0, 'color' => 'bg-red-500'],
                    ] as $row)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $row['label'] }}</span>
                                <span class="text-slate-500">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full {{ $row['color'] }}" style="width: {{ max(2, min(100, $row['bar'])) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ $sla['completion_rate'] }}% of follow-ups completed in the reporting window.</p>
            </div>

            <div class="rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-gw-primary">Lead Source Conversion</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($performanceReport['source_conversion'] as $source)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gw-primary">{{ $source['source'] }}</p>
                                <p class="text-xs text-slate-500">{{ $source['converted'] }} of {{ $source['prospects'] }} converted</p>
                            </div>
                            <span class="rounded-full bg-gw-accent-muted px-2 py-1 text-xs font-bold text-gw-accent-dark">{{ $source['conversion_rate'] }}%</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No source conversion data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-gw-primary">Agent / Team Rollup</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead class="text-slate-500">
                            <tr>
                                <th class="pb-2 font-semibold">Name</th>
                                <th class="pb-2 text-right font-semibold">Prospects</th>
                                <th class="pb-2 text-right font-semibold">Appts</th>
                                <th class="pb-2 text-right font-semibold">Conv.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($performanceReport['team_rollup'] as $member)
                                <tr>
                                    <td class="py-2 pr-3 font-medium text-gw-primary">{{ $member['name'] }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ $member['prospects'] }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ $member['appointments'] }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ $member['conversions'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gw-primary">Funnel Conversion</h2>
                        <p class="mt-1 text-sm text-slate-600">Stage counts and drop-off between stages.</p>
                    </div>
                    <select wire:model.live="funnelFilter" class="rounded-lg border-slate-300 text-sm font-semibold text-gw-primary">
                        <option value="insurance">Insurance Funnel</option>
                        <option value="recruiting">Recruiting Funnel</option>
                    </select>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($funnelConversion['stages'] as $stage)
                        @php($width = $funnelConversion['max_count'] > 0 ? round(($stage['count'] / $funnelConversion['max_count']) * 100) : 0)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-semibold text-gw-primary">{{ $stage['name'] }}</span>
                                <span class="text-slate-600">{{ $stage['count'] }} @if($stage['drop_off'] > 0)<span class="text-red-600">(-{{ $stage['drop_off'] }}%)</span>@endif</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100">
                                <div class="h-3 rounded-full bg-gw-accent" style="width: {{ max(2, $width) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No funnel stages configured.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Lead Sources</h2>
                    <p class="mt-1 text-sm text-slate-600">Prospects by acquisition source.</p>
                    <div class="mt-5 space-y-3">
                        @forelse ($leadSources as $source)
                            @php($width = $sourceMax > 0 ? round(($source['count'] / $sourceMax) * 100) : 0)
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="font-medium text-gw-primary">{{ $source['source'] }}</span>
                                    <span class="text-slate-600">{{ $source['count'] }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-gw-primary" style="width: {{ max(2, $width) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No lead source data yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Monthly Activity</h2>
                    <p class="mt-1 text-sm text-slate-600">Communications, activities, and appointments.</p>
                    <div class="mt-5 flex items-end gap-2" style="min-height: 10rem;">
                        @foreach ($activityTrend as $month)
                            @php($height = $activityMax > 0 ? round(($month['total'] / $activityMax) * 100) : 0)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                                <span class="text-[0.65rem] font-semibold text-gw-primary">{{ $month['total'] }}</span>
                                <div class="flex w-full flex-col justify-end rounded-t bg-slate-100" style="height: 7rem;">
                                    <div class="w-full rounded-t bg-gw-accent" style="height: {{ max(4, $height) }}%"></div>
                                </div>
                                <span class="truncate text-[0.6rem] text-slate-500">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Prospect Growth</h2>
                    <p class="mt-1 text-sm text-slate-600">Cumulative new prospects over six months.</p>
                    <div class="mt-5 space-y-3">
                        @foreach ($prospectGrowth as $month)
                            @php($width = $growthMax > 0 ? round(($month['cumulative'] / $growthMax) * 100) : 0)
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="font-medium text-gw-primary">{{ $month['label'] }}</span>
                                    <span class="text-slate-600">+{{ $month['new_count'] }} ({{ $month['cumulative'] }} total)</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-gw-accent-dark" style="width: {{ max(2, $width) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-gw-accent/30 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Dual Pipeline</h2>
                    <p class="mt-1 text-sm text-slate-600">Insurance vs recruiting by stage.</p>
                    <div class="mt-5 grid gap-6 md:grid-cols-2">
                        @foreach (['insurance' => 'Insurance', 'recruiting' => 'Recruiting'] as $key => $label)
                            <div>
                                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gw-accent">{{ $label }}</p>
                                <div class="space-y-2">
                                    @foreach (collect($dualPipeline[$key])->take(6) as $stage)
                                        @php($width = $dualPipeline['max_count'] > 0 ? round(($stage['count'] / $dualPipeline['max_count']) * 100) : 0)
                                        <div>
                                            <div class="mb-0.5 flex justify-between text-xs">
                                                <span class="truncate pr-2 text-gw-primary">{{ $stage['stage'] }}</span>
                                                <span class="text-slate-500">{{ $stage['count'] }}</span>
                                            </div>
                                            <div class="h-2 rounded-full bg-slate-100">
                                                <div @class([
                                                    'h-2 rounded-full',
                                                    'bg-gw-accent' => $key === 'insurance',
                                                    'bg-gw-primary' => $key === 'recruiting',
                                                ]) style="width: {{ max(2, $width) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <livewire:prospects.prospect-goals-panel />
        </aside>
    </div>
</div>
