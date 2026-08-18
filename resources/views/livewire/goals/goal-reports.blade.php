<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4">
        <h2 class="text-lg font-semibold text-gw-primary">Performance Reports</h2>
        <p class="text-sm text-slate-600">Download or email PDF summaries of your goal progress.</p>
    </div>

    <div class="flex flex-wrap items-end gap-4 border-b border-slate-100 bg-slate-50/80 p-4">
        <div>
            <label for="report-period" class="text-xs font-semibold uppercase text-slate-500">Report period</label>
            <select
                id="report-period"
                wire:model.live="periodType"
                class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                <option value="weekly">Last week</option>
                <option value="monthly">Last month</option>
                <option value="quarterly">Last quarter</option>
                <option value="annual">Last year</option>
            </select>
        </div>

        <a
            href="{{ route('goals.reports.download', ['period' => $periodType]) }}"
            wire:key="download-{{ $periodType }}"
            class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
        >
            Download PDF
        </a>

        <form method="POST" action="{{ route('goals.reports.email') }}" class="inline" wire:key="email-{{ $periodType }}">
            @csrf
            <input type="hidden" name="period" value="{{ $periodType }}">
            <button
                type="submit"
                class="rounded-lg border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-accent-dark hover:bg-gw-accent-muted"
            >
                Email report
            </button>
        </form>
    </div>

    <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Avg progress', 'value' => $preview['average_progress'].'%', 'theme' => 'navy'],
            ['label' => 'Goals', 'value' => $preview['goals']->count(), 'theme' => 'gold'],
            ['label' => 'Completed', 'value' => $preview['completed_count'], 'theme' => 'emerald'],
            ['label' => 'Off track', 'value' => $preview['off_track_count'], 'theme' => 'amber'],
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
            {{ $preview['period_label'] }} report:
            {{ $preview['period_start']->format('M j, Y') }} – {{ $preview['period_end']->format('M j, Y') }}
        </p>

        @if ($preview['goals']->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Goal</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Progress</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['goals'] as $goal)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gw-primary">{{ $goal->name }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $goal->category?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $goal->progressPercent() }}%</td>
                                <td class="px-3 py-2 text-slate-600">{{ config('goals.statuses.'.$goal->status, $goal->status) }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $goal->deadline_at?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-500">No goals found for this reporting period.</p>
        @endif
    </div>
</div>
