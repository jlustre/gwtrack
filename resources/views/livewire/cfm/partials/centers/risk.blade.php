@php($center = $sectionCenter)
@php($assessment = $center['assessment'] ?? [])

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Automated monitoring</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <button type="button" wire:click="runRiskAssessment" class="inline-flex rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Refresh assessment</button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Risk score', 'value' => ($center['stats']['score'] ?? 0).'/100', 'theme' => 'navy'],
            ['label' => 'Risk level', 'value' => ucfirst($center['stats']['level'] ?? 'low'), 'theme' => 'amber'],
            ['label' => 'Active flags', 'value' => $center['stats']['flags'] ?? 0, 'theme' => ($center['stats']['flags'] ?? 0) > 0 ? 'red' : 'emerald'],
            ['label' => 'Action plans', 'value' => $center['stats']['active_plans'] ?? 0, 'theme' => 'slate'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div @class([
        'rounded-xl border p-5 shadow-sm',
        'border-red-200 bg-red-50' => ($assessment['level'] ?? '') === 'high',
        'border-amber-200 bg-amber-50' => ($assessment['level'] ?? '') === 'medium',
        'border-emerald-200 bg-emerald-50' => ($assessment['level'] ?? '') === 'low',
    ])>
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Current assessment</h3>
            <span class="text-xs text-slate-600">Assessed {{ $assessment['assessed_at'] ?? '—' }}</span>
        </div>

        @if (count($assessment['flags'] ?? []) === 0)
            <p class="mt-4 text-sm text-emerald-800">No risk flags detected.</p>
        @else
            <ul class="mt-4 list-inside list-disc space-y-1 text-sm text-gw-primary">
                @foreach ($assessment['flags'] as $flag)
                    <li>{{ $flag }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4 border-t border-black/5 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Recommended actions</p>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                @foreach ($assessment['recommended_actions'] ?? [] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm xl:col-span-1">
            <h3 class="text-sm font-semibold text-gw-primary">Create action plan</h3>
            <form wire:submit="createActionPlan" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Title</label>
                    <input type="text" wire:model="actionPlanTitle" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="30-day recovery plan">
                    @error('actionPlanTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Summary</label>
                    <textarea wire:model="actionPlanSummary" rows="3" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Steps (one per line)</label>
                    <textarea wire:model="actionPlanSteps" rows="4" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Target date</label>
                    <input type="date" wire:model="actionPlanTargetDate" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                </div>
                <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Save plan</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Action plans</h3>
            </div>
            @if (count($center['action_plans']) === 0)
                <p class="p-6 text-sm text-slate-500">No action plans yet. Create one from recommended actions.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['action_plans'] as $plan)
                        <li wire:key="cfm-action-plan-{{ $plan['id'] }}" class="px-5 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                            'bg-emerald-100 text-emerald-800' => $plan['status'] === 'completed',
                                            'bg-sky-100 text-sky-800' => $plan['status'] === 'active',
                                            'bg-slate-100 text-slate-700' => $plan['status'] === 'cancelled',
                                        ])>{{ $plan['status'] }}</span>
                                        <span class="text-xs text-slate-500">Target {{ $plan['target_date'] }}</span>
                                    </div>
                                    <p class="mt-2 font-semibold text-gw-primary">{{ $plan['title'] }}</p>
                                    @if ($plan['summary'])
                                        <p class="mt-1 text-sm text-slate-600">{{ $plan['summary'] }}</p>
                                    @endif
                                    @if (count($plan['steps']) > 0)
                                        <ul class="mt-2 list-inside list-disc text-xs text-slate-600">
                                            @foreach ($plan['steps'] as $step)
                                                <li>{{ $step }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                @if ($plan['status'] === 'active')
                                    <button type="button" wire:click="completeActionPlan({{ $plan['id'] }})" class="rounded-lg bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary hover:bg-gw-accent-hover">Complete</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if (count($center['history']) > 0)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Assessment history</h3>
            </div>
            <ul class="divide-y divide-slate-200">
                @foreach ($center['history'] as $entry)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-slate-600">{{ $entry['assessed_at'] }}</span>
                        <span class="font-semibold text-gw-primary">{{ $entry['score'] }}/100 · {{ ucfirst($entry['level']) }} · {{ $entry['flag_count'] }} flags</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
