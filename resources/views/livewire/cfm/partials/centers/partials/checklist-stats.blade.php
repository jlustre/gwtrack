<div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach ([
        ['label' => 'Completion', 'value' => ($center['stats']['percent'] ?? 0).'%', 'tone' => 'gold'],
        ['label' => 'Completed', 'value' => ($center['stats']['completed'] ?? 0).'/'.($center['stats']['total'] ?? 0), 'tone' => 'default'],
        ['label' => 'Pending review', 'value' => $center['stats']['pending'] ?? 0, 'tone' => 'amber'],
        ['label' => 'Remaining', 'value' => $center['stats']['remaining'] ?? 0, 'tone' => 'default'],
    ] as $card)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
            <p @class([
                'mt-2 text-xl font-bold',
                'text-gw-accent-dark' => $card['tone'] === 'gold',
                'text-amber-700' => $card['tone'] === 'amber',
                'text-gw-primary' => $card['tone'] === 'default',
            ])>{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-2 flex justify-between text-sm">
        <span class="text-slate-600">Required progress</span>
        <span class="font-semibold text-gw-primary">{{ $center['stats']['required_percent'] ?? 0 }}%</span>
    </div>
    <div class="h-2 w-full rounded-full bg-slate-200">
        <div class="h-2 rounded-full bg-gw-accent" style="width: {{ min(100, (int) ($center['stats']['required_percent'] ?? 0)) }}%"></div>
    </div>
    <p class="mt-2 text-xs text-slate-500">{{ $center['stats']['required_completed'] ?? 0 }}/{{ $center['stats']['required_total'] ?? 0 }} required items complete</p>
</div>
