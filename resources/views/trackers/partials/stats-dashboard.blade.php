@php
    $completedLabel = $tracker['completedLabel'] ?? ($completedLabel ?? 'Items complete');
    $graphLabel = $tracker['graphLabel'] ?? ($graphLabel ?? 'Overall progress');
    $requiredLabel = $tracker['requiredLabel'] ?? ($requiredLabel ?? 'Required completion');
    $pendingPercent = $stats['total'] > 0 ? (int) round(($stats['pending'] / $stats['total']) * 100) : 0;
@endphp

<div class="grid gap-3 p-4 xl:grid-cols-5">
    <div class="grid gap-2 sm:grid-cols-2 xl:col-span-3 xl:grid-cols-3">
        {{-- Overall Progress --}}
        <article class="relative overflow-hidden rounded-lg border border-gw-accent/35 bg-gradient-to-br from-gw-accent-soft via-[#FFF0C9] to-[#F3D98A] p-3 shadow-sm">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-gw-accent/25 blur-sm" aria-hidden="true"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 h-10 w-full bg-gradient-to-t from-gw-accent/10 to-transparent" aria-hidden="true"></div>
            <div class="relative flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gw-accent-dark">Overall Progress</p>
                    <p class="mt-1 text-xl font-bold tabular-nums leading-none text-gw-primary">{{ $stats['completed'] }}/{{ $stats['total'] }}</p>
                    <p class="mt-1 text-[11px] leading-tight text-[#5C4A14]">{{ $completedLabel }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-gw-primary/10 px-2 py-0.5 text-[11px] font-bold tabular-nums text-gw-primary">{{ $stats['percent'] }}%</span>
            </div>
        </article>

        {{-- Required Items --}}
        <article class="relative overflow-hidden rounded-lg border border-gw-primary/15 bg-gradient-to-br from-[#E9EEF5] via-[#D5DEEA] to-[#B8C8DA] p-3 shadow-sm">
            <div class="pointer-events-none absolute -left-3 top-1/2 h-14 w-14 -translate-y-1/2 rounded-full bg-gw-primary/10" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gw-primary/70">Required Items</p>
                <p class="mt-1 text-xl font-bold tabular-nums leading-none text-gw-primary">{{ $stats['requiredCompleted'] }}/{{ $stats['requiredTotal'] }}</p>
                <p class="mt-1 text-[11px] leading-tight text-[#334155]">{{ $stats['requiredPercent'] }}% of required complete</p>
            </div>
        </article>

        {{-- Optional Items --}}
        <article class="relative overflow-hidden rounded-lg border border-cyan-200/80 bg-gradient-to-br from-cyan-50 via-sky-50 to-teal-100 p-3 shadow-sm">
            <div class="pointer-events-none absolute -right-2 bottom-0 h-12 w-12 rounded-tl-full bg-cyan-300/30" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-800/70">Optional Items</p>
                <p class="mt-1 text-xl font-bold tabular-nums leading-none text-gw-primary">{{ $stats['optionalCompleted'] }}/{{ $stats['optionalTotal'] }}</p>
                <p class="mt-1 text-[11px] leading-tight text-cyan-900/70">Growth &amp; enrichment</p>
            </div>
        </article>

        {{-- Pending --}}
        <article class="relative overflow-hidden rounded-lg border border-amber-200/90 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 p-3 shadow-sm">
            <div class="pointer-events-none absolute right-2 top-2 h-8 w-8 rounded-full border-2 border-amber-300/50" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-800/80">Pending</p>
                <p class="mt-1 text-xl font-bold tabular-nums leading-none text-amber-950">{{ $stats['pending'] }}</p>
                <p class="mt-1 text-[11px] leading-tight text-amber-900/70">Awaiting confirmation</p>
            </div>
        </article>

        {{-- Need Confirmation --}}
        <article class="relative overflow-hidden rounded-lg border border-violet-200/90 bg-gradient-to-br from-violet-50 via-purple-50 to-fuchsia-100 p-3 shadow-sm">
            <div class="pointer-events-none absolute -bottom-3 -right-3 h-16 w-16 rounded-full bg-violet-400/20" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] font-bold uppercase tracking-wider text-violet-800/80">Need Confirmation</p>
                <p class="mt-1 text-xl font-bold tabular-nums leading-none text-violet-950">{{ $stats['needsConfirmation'] }}</p>
                <p class="mt-1 text-[11px] leading-tight text-violet-900/70">Waiting on you</p>
            </div>
        </article>

        {{-- Remaining --}}
        <article class="relative overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 p-3 shadow-sm">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-slate-400/40 to-transparent" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Remaining</p>
                <p class="mt-1 text-xl font-bold tabular-nums leading-none text-gw-primary">{{ $stats['remaining'] }}</p>
                <p class="mt-1 text-[11px] leading-tight text-slate-600">Next to complete</p>
            </div>
        </article>
    </div>

    {{-- Progress Graph --}}
    <div class="xl:col-span-2">
        <div class="relative h-full overflow-hidden rounded-lg border border-gw-primary/25 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-[#1E3A5F] p-3 text-white shadow-md">
            <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gw-accent/15 blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-6 -left-6 h-20 w-20 rounded-full bg-white/5" aria-hidden="true"></div>

            <div class="relative flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-white">Progress Snapshot</h2>
                    <p class="mt-0.5 text-[11px] leading-tight text-slate-300">Completed, pending, and required at a glance.</p>
                </div>
                <div class="shrink-0 rounded-md bg-white/10 px-2 py-1 text-[10px] font-semibold tabular-nums text-slate-200">
                    {{ $stats['completed'] }} · {{ $stats['pending'] }} · {{ $stats['remaining'] }}
                </div>
            </div>

            <div class="relative mt-3 space-y-2.5">
                <div>
                    <div class="mb-1 flex items-center justify-between text-[11px]">
                        <span class="font-medium text-slate-300">{{ $graphLabel }}</span>
                        <span class="font-bold tabular-nums text-gw-accent-hover">{{ $stats['percent'] }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-gradient-to-r from-gw-accent to-gw-accent-hover transition-all" style="width: {{ $stats['percent'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between text-[11px]">
                        <span class="font-medium text-slate-300">{{ $requiredLabel }}</span>
                        <span class="font-bold tabular-nums text-white">{{ $stats['requiredPercent'] }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-white/80 transition-all" style="width: {{ $stats['requiredPercent'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between text-[11px]">
                        <span class="font-medium text-slate-300">Pending confirmation</span>
                        <span class="font-bold tabular-nums text-amber-300">{{ $stats['pending'] }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-amber-400 transition-all" style="width: {{ $pendingPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
