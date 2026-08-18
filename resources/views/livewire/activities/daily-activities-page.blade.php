@php
    $formatValue = function (array $row): string {
        if (($row['format'] ?? 'number') === 'currency') {
            return '$'.number_format((float) $row['actual'], 0);
        }

        return number_format((float) $row['actual'], (floor($row['actual']) == $row['actual'] ? 0 : 1));
    };
    $formatTarget = function (array $row): string {
        if (($row['format'] ?? 'number') === 'currency') {
            return '$'.number_format((float) $row['target'], 0);
        }

        return number_format((float) $row['target'], (floor($row['target']) == $row['target'] ? 0 : 1));
    };
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Workspace</p>
                    <h1 class="mt-2 text-2xl font-semibold">My Daily Activities</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                        Track today’s activity rhythm. Leads, production, and recruits come from the system; calls, follow-ups, invitations, and presentations can be tallied here and stay in sync with Goals.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('manage goals')
                        <a
                            href="{{ route('goals.index', ['tab' => 'scorecard']) }}"
                            class="inline-flex items-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:border-gw-accent hover:bg-white/15"
                        >
                            Goals Scorecard
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-4 py-3">
            <p class="text-sm text-slate-600">
                Showing <span class="font-semibold text-gw-primary">{{ $dashboard['period_label'] }}</span>
                · Overall pace <span class="font-semibold text-gw-accent-dark">{{ $dashboard['overall_score'] }}%</span>
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($periods as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('period', '{{ $key }}')"
                        @class([
                            'rounded-full px-3 py-1.5 text-xs font-semibold transition',
                            'bg-gw-primary text-white' => $period === $key,
                            'border border-slate-300 text-slate-600 hover:border-gw-accent' => $period !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    @if ($statusMessage)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ $statusMessage }}
        </div>
    @endif

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">System generated</h2>
            <p class="text-sm text-slate-600">These update when you add prospects, production, or recruits in the app.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($dashboard['system'] as $row)
                <div wire:key="system-{{ $row['key'] }}" class="overflow-visible rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</p>
                                <x-checklist-description-help :text="$row['description'] ?? null" :examples="$row['examples'] ?? []" class="h-5 w-5 text-[10px]" />
                            </div>
                            <p class="mt-2 text-3xl font-bold text-gw-primary">{{ $formatValue($row) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $row['subtitle'] }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                            Goal {{ $formatTarget($row) }}
                        </span>
                    </div>
                    @if ($row['href'])
                        <a href="{{ $row['href'] }}" class="mt-4 inline-flex text-xs font-semibold text-gw-accent-dark hover:underline">
                            Open source →
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Log today’s activities</h2>
            <p class="text-sm text-slate-600">
                Use + / − to tally today’s work. Period totals include CRM activity plus your manual tallies.
            </p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
            @foreach ($dashboard['manual'] as $row)
                <div wire:key="manual-{{ $row['key'] }}" class="overflow-visible rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</p>
                        <x-checklist-description-help :text="$row['description'] ?? null" :examples="$row['examples'] ?? []" class="h-5 w-5 text-[10px]" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-gw-primary">{{ $formatValue($row) }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Goal {{ $formatTarget($row) }}
                        · Today’s tally {{ number_format($row['today_manual'], 0) }}
                    </p>
                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="decrement('{{ $row['key'] }}')"
                            wire:loading.attr="disabled"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 bg-white text-lg font-bold text-gw-primary hover:border-gw-accent disabled:opacity-50"
                            aria-label="Decrease {{ $row['label'] }}"
                        >
                            −
                        </button>
                        <button
                            type="button"
                            wire:click="increment('{{ $row['key'] }}')"
                            wire:loading.attr="disabled"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-gw-primary bg-gw-primary text-lg font-bold text-white hover:bg-gw-primary-hover disabled:opacity-50"
                            aria-label="Increase {{ $row['label'] }}"
                        >
                            +
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Actual vs Goal</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $dashboard['period_label'] }} · synced with Goals targets</p>
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
            @foreach ($dashboard['chart'] as $row)
                @php
                    $max = max((float) $row['actual'], (float) $row['target'], 1);
                    $actualWidth = min(100, round(((float) $row['actual'] / $max) * 100));
                    $targetWidth = min(100, round(((float) $row['target'] / $max) * 100));
                @endphp
                <div wire:key="chart-{{ $row['key'] }}">
                    <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                        <p class="font-semibold text-gw-primary">{{ $row['label'] }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $formatValue($row) }} / {{ $formatTarget($row) }}
                            <span class="ml-1 font-semibold text-gw-accent-dark">{{ $row['percent'] }}%</span>
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
    </section>
</div>
