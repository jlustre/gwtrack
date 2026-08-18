<div class="grid gap-6 lg:grid-cols-2">
  @if ($blueprint)
        <div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-accent-soft to-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Active blueprint</p>
            <h3 class="mt-1 text-lg font-semibold text-gw-primary">{{ $blueprint->name }}</h3>
            <p class="mt-2 text-sm text-slate-600">Track your full funnel from outcome to daily activities.</p>
            <a href="{{ route('goals.blueprint.show', $blueprint) }}" class="mt-4 inline-flex rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Open Success Blueprint</a>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gw-primary">Performance forecast</h3>
        @if ($forecasts === [])
            <p class="mt-3 text-sm text-slate-600">Create a performance plan to see pace projections.</p>
        @else
            <ul class="mt-3 space-y-2">
                @foreach ($forecasts as $forecast)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <span class="text-gw-primary">{{ $forecast['goal_name'] }}</span>
                        <span @class([
                            'font-semibold',
                            'text-emerald-700' => $forecast['projected_percent'] >= 100,
                            'text-amber-700' => $forecast['projected_percent'] < 80,
                            'text-slate-600' => $forecast['projected_percent'] >= 80 && $forecast['projected_percent'] < 100,
                        ])>{{ $forecast['projected_percent'] }}%</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($alerts->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-semibold text-amber-900">Coaching alerts</h3>
            <ul class="mt-3 space-y-2">
                @foreach ($alerts as $alert)
                    <li class="text-sm text-amber-800"><span class="font-semibold">{{ $alert->title }}:</span> {{ $alert->message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
