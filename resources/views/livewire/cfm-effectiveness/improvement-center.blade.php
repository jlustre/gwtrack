<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Improvement Center</h2>
            <p class="text-sm text-slate-600">Action plans generated from objective metrics and trainee feedback trends.</p>
        </div>
        @can('manage action plans')
            <button wire:click="generatePlans" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-soft">Generate Action Plans</button>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <h3 class="font-semibold text-gw-primary">Recommendations</h3>
            <ul class="mt-4 space-y-3">
                @forelse ($recommendations as $rec)
                    <li class="rounded-lg border border-gw-accent/20 bg-gw-accent-soft/40 px-3 py-2 text-sm">
                        <p class="font-semibold text-gw-primary">{{ $rec['area'] }} <span class="text-xs uppercase text-slate-500">({{ $rec['priority'] }})</span></p>
                        <p class="mt-1 text-slate-600">{{ $rec['suggestion'] }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-600">No active improvement areas identified.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <h3 class="font-semibold text-gw-primary">Active Action Plans</h3>
            <div class="mt-4 space-y-4">
                @forelse ($actionPlans as $plan)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-gw-primary">{{ $plan->improvement_area }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $plan->target_outcome }}</p>
                        <div class="mt-3 h-2 rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $plan->progress }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $plan->progress }}% complete @if($plan->due_date) · Due {{ $plan->due_date->format('M j, Y') }} @endif</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No active action plans.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
