<div class="space-y-6">
    <div class="overflow-hidden rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Success Blueprint</p>
                <h1 class="mt-2 text-2xl font-semibold">{{ $blueprint->name }}</h1>
                <p class="mt-2 text-sm text-slate-200">{{ config('goals-planning.planning_types.'.$blueprint->planning_type.'.label', $blueprint->planning_type) }} &middot; Target {{ number_format((float) $blueprint->root_target_value, 0) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase text-slate-300">Projected completion</p>
                <p class="text-3xl font-bold text-gw-accent">{{ $forecasts['projected_percent'] ?? 0 }}%</p>
            </div>
        </div>
    </div>

    @if (! empty($forecasts['recommended_actions']))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">Recommended actions</p>
            <ul class="mt-2 space-y-1 text-sm text-amber-800">
                @foreach ($forecasts['recommended_actions'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">Goal dependency funnel</h2>
        <p class="mt-1 text-sm text-slate-600">Outcome goals at the top flow down to the daily activities required to achieve them.</p>

        <div class="mt-6 space-y-4">
            @foreach ($stages as $index => $stage)
                <div wire:key="blueprint-stage-{{ $stage['key'] }}" class="relative rounded-xl border border-slate-200 p-4 {{ $index === 0 ? 'border-gw-accent/50 bg-gw-accent-soft/30' : 'bg-white' }}">
                    @if (! $loop->last)
                        <div class="absolute -bottom-4 left-8 z-10 text-gw-accent">↓</div>
                    @endif
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gw-accent">{{ ucfirst($stage['goal_type'] ?? 'goal') }}</p>
                            <h3 class="text-base font-semibold text-gw-primary">{{ $stage['label'] }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gw-primary">{{ $stage['progress_percent'] ?? 0 }}%</p>
                            <p class="text-xs text-slate-500">Projected {{ $stage['projected_percent'] ?? 0 }}%</p>
                        </div>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                        <div @class([
                            'h-2 rounded-full',
                            'bg-emerald-500' => ($stage['progress_percent'] ?? 0) >= 100,
                            'bg-amber-500' => ($stage['pace_status'] ?? '') === 'behind',
                            'bg-gw-accent' => ($stage['progress_percent'] ?? 0) < 100 && ($stage['pace_status'] ?? '') !== 'behind',
                        ]) style="width: {{ $stage['progress_percent'] ?? 0 }}%"></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                        <span>Target: {{ number_format($stage['target_value'] ?? $stage['annual_target'] ?? 0, 0) }}</span>
                        <span>Actual: {{ number_format($stage['actual_value'] ?? 0, 0) }}</span>
                        <span>Monthly: {{ number_format($stage['monthly_target'] ?? 0, 0) }}</span>
                        <span>Daily: {{ number_format($stage['daily_target'] ?? 0, 1) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
