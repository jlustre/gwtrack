<div class="space-y-6">
    @if (! ($report['visible'] ?? false))
        <p class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
            Agency reports are available when you have team members in your downline hierarchy.
        </p>
    @else
        <div class="rounded-lg border border-gw-primary/20 bg-gradient-to-br from-gw-primary via-[#102A4C] to-gw-primary p-6 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Agency Overview</p>
            <h2 class="mt-1 text-lg font-semibold">FNA activity across your hierarchy</h2>
            <p class="mt-1 text-sm text-slate-300">{{ $report['member_count'] }} team members in scope</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">By Associate</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Associate</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3">Approved</th>
                            <th class="px-4 py-3">Avg Gap</th>
                            <th class="px-4 py-3">Avg Review (h)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['by_associate'] ?? [] as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gw-primary">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['created'] }}</td>
                                <td class="px-4 py-3">{{ $row['submitted'] }}</td>
                                <td class="px-4 py-3">{{ $row['approved'] }}</td>
                                <td class="px-4 py-3">{{ $row['avg_gap'] !== null ? '$'.number_format($row['avg_gap'], 0) : '—' }}</td>
                                <td class="px-4 py-3">{{ $row['avg_review_hours'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-600">No associate FNA activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">By CFM</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">CFM</th>
                            <th class="px-4 py-3">Reviews</th>
                            <th class="px-4 py-3">Approval Rate</th>
                            <th class="px-4 py-3">Avg Turnaround (h)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['by_cfm'] ?? [] as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gw-primary">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['review_count'] }}</td>
                                <td class="px-4 py-3">{{ $row['approval_rate'] }}%</td>
                                <td class="px-4 py-3">{{ $row['avg_turnaround_hours'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-600">No CFM review activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (collect($trends)->sum('total_fnas') > 0)
            <div class="rounded-xl border border-gw-accent/30 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">12-Week Trend</h2>
                <div class="mt-6 flex items-end gap-2 overflow-x-auto pb-2" style="min-height: 8rem;">
                    @foreach ($trends as $point)
                        @php($height = $trendMax > 0 ? round(($point['approved_fnas'] / $trendMax) * 100) : 0)
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
    @endif
</div>
