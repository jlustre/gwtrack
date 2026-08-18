@php
    $formatValue = function (float $value, string $format = 'number'): string {
        if ($format === 'currency') {
            return '$'.number_format($value, 0);
        }

        return number_format($value, (floor($value) == $value ? 0 : 1));
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Activity Scorecard</h2>
            <p class="text-sm text-slate-600">{{ $scorecard['period_label'] }} · synced with My Daily Activities</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($periods as $key => $label)
                <button type="button" wire:click="$set('periodType', '{{ $key }}')" @class([
                    'rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-gw-primary text-white' => $periodType === $key,
                    'border border-slate-300 text-slate-600' => $periodType !== $key,
                ])>{{ $label }}</button>
            @endforeach
            <a
                href="{{ route('daily-activities.index') }}"
                class="rounded-full border border-gw-accent bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary hover:bg-gw-accent-muted"
            >
                Log today’s activities
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between rounded-xl border border-gw-accent/30 bg-gw-accent-soft/40 px-5 py-4">
            <span class="text-sm font-semibold text-gw-primary">Overall activity score</span>
            <span class="text-3xl font-bold text-gw-accent-dark">{{ $scorecard['overall_score'] }}%</span>
        </div>

        <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($scorecard['activities'] as $activity)
                <div wire:key="score-{{ $activity['key'] }}" class="overflow-visible rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <p class="font-semibold text-gw-primary">{{ $activity['label'] }}</p>
                            <x-checklist-description-help :text="$activity['description'] ?? null" :examples="$activity['examples'] ?? []" />
                        </div>
                        <span class="text-sm font-bold text-slate-600">{{ $activity['percent'] }}%</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $formatValue((float) $activity['actual'], $activity['format'] ?? 'number') }}
                        /
                        {{ $formatValue((float) $activity['target'], $activity['format'] ?? 'number') }}
                    </p>
                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $activity['percent'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gw-primary">Actual vs Goal</h3>
                    <p class="text-xs text-slate-500">Double-bar view of the same metrics shown on My Daily Activities</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-gw-primary">
                        <span class="h-2.5 w-2.5 rounded-sm bg-gw-primary"></span> Actual
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-gw-accent">
                        <span class="h-2.5 w-2.5 rounded-sm bg-gw-accent"></span> Goal
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ($scorecard['activities'] as $activity)
                    @php
                        $max = max((float) $activity['actual'], (float) $activity['target'], 1);
                        $actualWidth = min(100, round(((float) $activity['actual'] / $max) * 100));
                        $targetWidth = min(100, round(((float) $activity['target'] / $max) * 100));
                    @endphp
                    <div wire:key="chart-{{ $activity['key'] }}">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <p class="font-semibold text-gw-primary">{{ $activity['label'] }}</p>
                                <x-checklist-description-help :text="$activity['description'] ?? null" :examples="$activity['examples'] ?? []" />
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ $formatValue((float) $activity['actual'], $activity['format'] ?? 'number') }}
                                /
                                {{ $formatValue((float) $activity['target'], $activity['format'] ?? 'number') }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-12 shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Actual</span>
                                <div class="h-3 flex-1 rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-gw-primary" style="width: {{ $actualWidth }}%"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-12 shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Goal</span>
                                <div class="h-3 flex-1 rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-gw-accent" style="width: {{ $targetWidth }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
