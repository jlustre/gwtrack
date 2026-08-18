<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4">
        <h2 class="text-lg font-semibold text-gw-primary">Effectiveness Reports</h2>
        <p class="text-sm text-slate-600">
            Generate PDF summaries of mentorship effectiveness, retention, licensing, FAP completion, and agency comparisons.
        </p>
    </div>

    <div class="flex flex-wrap items-end gap-4 border-b border-slate-100 bg-slate-50/80 p-4">
        @if ($center['can_select_cfm'] && ! empty($cfmOptions))
            <div>
                <label for="report-cfm" class="text-xs font-semibold uppercase text-slate-500">CFM</label>
                <select
                    id="report-cfm"
                    wire:model.live="cfmId"
                    class="mt-1 block min-w-[12rem] rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                    <option value="">Select CFM…</option>
                    @foreach ($cfmOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="report-type" class="text-xs font-semibold uppercase text-slate-500">Report type</label>
            <select
                id="report-type"
                wire:model.live="reportType"
                class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                @foreach ($center['report_types'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="report-period" class="text-xs font-semibold uppercase text-slate-500">Period</label>
            <select
                id="report-period"
                wire:model.live="periodType"
                class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                <option value="monthly">Last month</option>
                <option value="quarterly">Last quarter</option>
                <option value="annual">Last year</option>
            </select>
        </div>

        <button
            type="button"
            wire:click="generateReport"
            wire:loading.attr="disabled"
            class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="generateReport">Generate &amp; Download PDF</span>
            <span wire:loading wire:target="generateReport">Generating…</span>
        </button>
    </div>

    <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Effectiveness score', 'value' => $preview['effectiveness_score'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Trainee satisfaction', 'value' => ($preview['trainee_satisfaction'] ?? 0).'%', 'theme' => 'emerald'],
            ['label' => 'Open coaching items', 'value' => $preview['open_coaching_items'] ?? 0, 'theme' => 'cyan'],
            ['label' => 'Active risks', 'value' => count($preview['risks'] ?? []), 'theme' => 'amber'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="border-t border-slate-100 p-4">
        <p class="text-xs text-slate-500">
            {{ $preview['period_label'] ?? 'Quarterly' }} preview for
            <strong>{{ $center['cfm']['name'] }}</strong>:
            {{ \Carbon\Carbon::parse($preview['period_start'])->format('M j, Y') }}
            –
            {{ \Carbon\Carbon::parse($preview['period_end'])->format('M j, Y') }}
        </p>

        @if (! empty($preview['objective_metrics']))
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Metric</th>
                            <th class="px-3 py-2">Score</th>
                            <th class="px-3 py-2">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['objective_metrics'] as $metric)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gw-primary">{{ $metric['label'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $metric['score'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $metric['detail'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if (! empty($center['history']))
        <div class="border-t border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-gw-primary">Recent reports</h3>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($center['history'] as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <div>
                            <span class="font-medium text-gw-primary">{{ $item['type_label'] }}</span>
                            <span class="text-slate-500">· {{ $item['generated_at'] }} · {{ $item['generated_by'] }}</span>
                        </div>
                        <a
                            href="{{ $item['download_url'] }}"
                            class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary"
                        >
                            Download
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
