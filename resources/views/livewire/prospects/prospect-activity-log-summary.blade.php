<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-gradient-to-r from-gw-primary to-[#102A4C] px-5 py-4 text-white">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Activity logs</p>
                <h2 class="mt-1 text-lg font-semibold">Prospecting activity summary</h2>
                <p class="mt-1 text-sm text-slate-300">{{ $rangeLabel }} · grouped {{ $summary['grouping'] }}</p>
            </div>
            <a href="{{ route('team.prospects.analytics') }}" class="shrink-0 text-sm font-semibold text-gw-accent hover:text-white">
                Full analytics →
            </a>
        </div>
    </div>

    <div class="space-y-5 p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'today' => 'Today',
                    'week' => 'This week',
                    'month' => 'This month',
                    'last_30' => 'Last 30 days',
                ] as $preset => $label)
                    <button
                        type="button"
                        wire:click="applyPreset('{{ $preset }}')"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="activity-log-start" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input
                        id="activity-log-start"
                        type="date"
                        wire:model.live="startDate"
                        class="rounded-lg border-slate-300 text-sm text-gw-primary"
                    />
                </div>
                <div>
                    <label for="activity-log-end" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input
                        id="activity-log-end"
                        type="date"
                        wire:model.live="endDate"
                        class="rounded-lg border-slate-300 text-sm text-gw-primary"
                    />
                </div>
                <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                    @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('grouping', '{{ $value }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition',
                                'bg-gw-primary text-gw-accent' => $grouping === $value,
                                'text-slate-600 hover:text-gw-primary' => $grouping !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            @foreach ($metricDefinitions as $metricKey => $definition)
                @php($total = $summary['totals'][$metricKey] ?? 0)
                <x-tracker-stat-card
                    :label="$definition['short_label'] ?? $definition['label']"
                    :value="number_format($total)"
                    :subtitle="$definition['description'] ?? null"
                    :theme="$definition['accent'] ?? 'gold'"
                />
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                            Period
                        </th>
                        @foreach ($metricDefinitions as $metricKey => $definition)
                            <th scope="col" class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-600">
                                {{ $definition['short_label'] ?? $definition['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($summary['buckets'] as $bucket)
                        <tr wire:key="activity-bucket-{{ $bucket['key'] }}" class="hover:bg-gw-accent-soft/40">
                            <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-3 font-semibold text-gw-primary">
                                {{ $bucket['label'] }}
                            </td>
                            @foreach ($metricDefinitions as $metricKey => $definition)
                                @php($value = $bucket['metrics'][$metricKey] ?? 0)
                                <td class="px-3 py-3 text-center">
                                    <span @class([
                                        'inline-flex min-w-[2rem] justify-center rounded-full px-2 py-0.5 font-semibold',
                                        'bg-gw-primary text-gw-accent' => $value > 0,
                                        'text-slate-400' => $value === 0,
                                    ])>
                                        {{ $value }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($metricDefinitions) + 1 }}" class="px-4 py-8 text-center text-slate-500">
                                No activity recorded for this date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($summary['buckets']) > 1)
                    <tfoot class="bg-gw-accent-soft/60">
                        <tr>
                            <td class="sticky left-0 z-10 bg-gw-accent-soft/60 px-4 py-3 font-bold text-gw-primary">Total</td>
                            @foreach ($metricDefinitions as $metricKey => $definition)
                                <td class="px-3 py-3 text-center font-bold text-gw-primary">
                                    {{ number_format($summary['totals'][$metricKey] ?? 0) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
