@php
    $analytics = $data['success_analytics'];
    $formatDays = fn (?float $days) => $days === null ? '—' : number_format($days, 0).' days';
@endphp

<div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Trainee Success Analytics</p>
            <h3 class="mt-1 text-xl font-semibold">Development Velocity</h3>
            <p class="mt-1 text-sm text-slate-300">Average days from assignment start to key milestones, compared with agency and top CFM benchmarks.</p>
        </div>
        <p class="text-xs text-slate-400">{{ $analytics['trainee_count'] }} trainees in history</p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['key' => 'time_to_license', 'label' => 'Time to License', 'value' => $analytics['avg_time_to_license_days'], 'sample' => $analytics['sample_sizes']['licensed'] ?? 0],
            ['key' => 'time_to_fap', 'label' => 'Time to Complete FAP', 'value' => $analytics['avg_time_to_fap_days'], 'sample' => $analytics['sample_sizes']['fap_complete'] ?? 0],
            ['key' => 'time_to_first_sale', 'label' => 'Time to First Sale', 'value' => $analytics['avg_time_to_first_sale_days'], 'sample' => $analytics['sample_sizes']['first_sale'] ?? 0],
            ['key' => 'time_to_first_recruit', 'label' => 'Time to First Recruit', 'value' => $analytics['avg_time_to_first_recruit_days'], 'sample' => $analytics['sample_sizes']['first_recruit'] ?? 0],
        ] as $metric)
            @php $compare = $analytics['cfm_vs_agency'][$metric['key']] ?? null; @endphp
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-bold">{{ $formatDays($metric['value']) }}</p>
                <p class="mt-1 text-xs text-slate-400">Based on {{ $metric['sample'] }} trainee{{ $metric['sample'] === 1 ? '' : 's' }}</p>
                @if ($compare && ($compare['agency'] !== null || $compare['top_cfm'] !== null))
                    <dl class="mt-3 space-y-1 text-xs text-slate-300">
                        @if ($compare['agency'] !== null)
                            <div class="flex justify-between gap-2">
                                <dt>Agency avg</dt>
                                <dd>{{ $formatDays($compare['agency']) }}</dd>
                            </div>
                        @endif
                        @if ($compare['top_cfm'] !== null)
                            <div class="flex justify-between gap-2">
                                <dt>Top CFM</dt>
                                <dd class="text-gw-accent">{{ $formatDays($compare['top_cfm']) }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>
        @endforeach
    </div>

    @if (! empty($analytics['trainees']))
        <div class="mt-6 overflow-hidden rounded-xl border border-white/10 bg-white/5">
            <div class="border-b border-white/10 px-4 py-3">
                <h4 class="text-sm font-semibold text-white">Recent Trainee Timelines</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Trainee</th>
                            <th class="px-4 py-3">License</th>
                            <th class="px-4 py-3">FAP</th>
                            <th class="px-4 py-3">First Sale</th>
                            <th class="px-4 py-3">First Recruit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-200">
                        @foreach ($analytics['trainees'] as $trainee)
                            <tr>
                                <td class="px-4 py-3 font-medium text-white">{{ $trainee['name'] }}</td>
                                <td class="px-4 py-3">{{ $formatDays($trainee['time_to_license_days'] ?? null) }}</td>
                                <td class="px-4 py-3">{{ $formatDays($trainee['time_to_fap_days'] ?? null) }}</td>
                                <td class="px-4 py-3">{{ $formatDays($trainee['time_to_first_sale_days'] ?? null) }}</td>
                                <td class="px-4 py-3">{{ $formatDays($trainee['time_to_first_recruit_days'] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
