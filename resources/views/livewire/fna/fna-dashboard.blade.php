<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total FNAs', 'value' => $summary['total_fnas'], 'theme' => 'navy', 'subtitle' => 'All FNA records'],
                ['label' => 'Draft FNAs', 'value' => $summary['draft_fnas'], 'theme' => 'slate', 'subtitle' => 'Still in progress'],
                ['label' => 'Awaiting CFM Review', 'value' => $summary['awaiting_review'], 'theme' => 'amber', 'subtitle' => 'Submitted for review'],
                ['label' => 'Approved FNAs', 'value' => $summary['approved_fnas'], 'theme' => 'emerald', 'subtitle' => 'CFM approved'],
                ['label' => 'Revision Requested', 'value' => $summary['revision_requested'], 'theme' => 'red', 'subtitle' => 'Needs updates'],
                ['label' => 'DIME Completed', 'value' => $summary['dime_completed'], 'theme' => 'gold', 'subtitle' => 'Protection analysis done'],
                ['label' => 'Meetings This Week', 'value' => $summary['meetings_this_week'], 'theme' => 'cyan', 'subtitle' => 'Scheduled client meetings'],
                ['label' => 'Avg Protection Gap', 'value' => $summary['avg_protection_gap'] !== null ? '$'.number_format($summary['avg_protection_gap'], 0) : '—', 'theme' => 'violet', 'subtitle' => 'Average coverage gap'],
                ['label' => 'Conversion After FNA', 'value' => $summary['conversion_after_fna'].'%', 'theme' => 'emerald', 'subtitle' => 'Post-FNA conversion'],
                ['label' => 'Avg CFM Review Time', 'value' => $summary['avg_cfm_review_hours'] !== null ? $summary['avg_cfm_review_hours'].'h' : '—', 'theme' => 'slate', 'subtitle' => 'Time to CFM decision'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gw-primary">Awaiting CFM Review</h2>
                @can('review trainee fna records')
                    <a href="{{ route('team.fna.cfm.review-queue') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">View queue</a>
                @endcan
            </div>
            @if ($awaitingReview->isEmpty())
                <p class="mt-4 text-sm text-slate-600">No FNAs awaiting review.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($awaitingReview as $record)
                        <li class="flex items-center justify-between py-3">
                            <div>
                                <a href="{{ route('team.fna.show', $record) }}" class="font-semibold text-gw-accent-dark hover:text-gw-primary">{{ $record->reference_code }}</a>
                                <p class="text-sm text-slate-600">{{ $record->client_name }}</p>
                            </div>
                            <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-0.5 text-xs font-semibold text-gw-primary">{{ $record->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Revision Requested</h2>
            @if ($revisionRequested->isEmpty())
                <p class="mt-4 text-sm text-slate-600">No FNAs need revision.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($revisionRequested as $record)
                        <li class="flex items-center justify-between py-3">
                            <div>
                                <a href="{{ route('team.fna.show', $record) }}" class="font-semibold text-gw-accent-dark hover:text-gw-primary">{{ $record->reference_code }}</a>
                                <p class="text-sm text-slate-600">{{ $record->client_name }}</p>
                            </div>
                            <span class="rounded-full border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Revision</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Meetings This Week</h2>
            @if ($meetings->isEmpty())
                <p class="mt-4 text-sm text-slate-600">No FNA meetings scheduled this week.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($meetings as $meeting)
                        <li class="py-3">
                            <p class="font-semibold text-gw-primary">{{ $meeting->title ?? 'FNA Meeting' }}</p>
                            <p class="text-sm text-slate-600">{{ \Carbon\Carbon::parse($meeting->starts_at)->format('D, M j g:i A') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Status Breakdown</h2>
            <div class="mt-4 space-y-3">
                @foreach ($progress['segments'] as $segment)
                    @php($width = $progressTotal > 0 ? round(($segment['count'] / $progressTotal) * 100) : 0)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-semibold text-gw-primary">{{ $segment['label'] }}</span>
                            <span class="text-slate-600">{{ $segment['count'] }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-slate-100">
                            <div class="h-2.5 rounded-full bg-gw-accent" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if (collect($trends)->sum('total_fnas') > 0)
        <div class="rounded-xl border border-gw-accent/30 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">12-Week Trend</h2>
            <div class="mt-6 flex items-end gap-2 overflow-x-auto pb-2" style="min-height: 8rem;">
                @foreach ($trends as $point)
                    @php($height = $trendMax > 0 ? round(($point['total_fnas'] / $trendMax) * 100) : 0)
                    <div class="flex min-w-[2.5rem] flex-col items-center gap-1">
                        <div class="flex w-full flex-col justify-end rounded-t bg-gw-primary/10" style="height: 6rem;">
                            <div class="w-full rounded-t bg-gw-accent" style="height: {{ max(4, $height) }}%"></div>
                        </div>
                        <span class="text-[10px] font-semibold text-slate-500">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('team.fna.create') }}" class="inline-flex items-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">+ New FNA</a>
        <a href="{{ route('team.fna.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-slate-50">View All FNAs</a>
        <a href="{{ route('team.fna.dime') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-slate-50">DIME Calculator</a>
    </div>
</div>
