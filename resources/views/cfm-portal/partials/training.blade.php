<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gw-primary">CFM Training Progress</h3>
        <a href="{{ route('cfm-training.index') }}" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-accent">View checklist →</a>
    </div>

    <div class="mb-4">
        <div class="mb-2 flex justify-between text-sm">
            <span class="text-slate-600">Overall progress</span>
            <span class="font-medium text-gw-primary">{{ $training['percent'] }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-slate-200">
            <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $training['percent'] }}%"></div>
        </div>
        <p class="mt-2 text-xs text-slate-500">
            {{ $training['completed'] }}/{{ $training['total'] }} modules complete
            · Required: {{ $training['requiredCompleted'] }}/{{ $training['requiredTotal'] }} ({{ $training['requiredPercent'] }}%)
        </p>
    </div>

    @if (count($training['modules']) > 0)
        <ul class="max-h-64 space-y-2 overflow-y-auto">
            @foreach ($training['modules'] as $module)
                <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-gw-primary">{{ $module['title'] }}</span>
                    <span @class([
                        'text-xs font-medium',
                        'text-emerald-700' => $module['isCompleted'],
                        'text-amber-700' => $module['isPending'],
                        'text-slate-500' => ! $module['isCompleted'] && ! $module['isPending'],
                    ])>{{ $module['status'] }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-slate-500">No CFM training modules are configured yet.</p>
    @endif
</div>
