<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-gw-primary to-gw-primary-hover px-6 py-5 text-white">
        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Performance Planner</p>
        <h2 class="mt-1 text-xl font-semibold">Build your Success Blueprint</h2>
        <p class="mt-2 text-sm text-slate-200">Reverse-engineer income, production, recruiting, or rank goals into daily activities.</p>
    </div>

    <div class="p-6">
        @if ($step === 1)
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($planningTypes as $key => $type)
                    <button
                        type="button"
                        wire:click="selectPlanningType('{{ $key }}')"
                        wire:key="plan-type-{{ $key }}"
                        @class([
                            'rounded-xl border p-5 text-left transition',
                            'border-gw-accent bg-gw-accent-soft ring-2 ring-gw-accent/30' => $planningType === $key,
                            'border-slate-200 hover:border-gw-accent/50' => $planningType !== $key,
                        ])
                    >
                        <p class="font-semibold text-gw-primary">{{ $type['label'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $type['description'] }}</p>
                    </button>
                @endforeach
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="target_value" value="Target value" />
                        <x-checklist-description-help :text="$fieldHelp['plan_target_value']['description'] ?? null" :examples="$fieldHelp['plan_target_value']['examples'] ?? []" />
                    </div>
                    <x-text-input id="target_value" type="number" step="0.01" wire:model.live="targetValue" class="mt-1 block w-full" placeholder="{{ $planningType === 'income' ? '100000' : '24' }}" />
                    <x-input-error :messages="$errors->get('targetValue')" />
                </div>
                @if ($planningType === 'rank')
                    <div class="overflow-visible">
                        <div class="flex items-center gap-2">
                            <x-input-label for="target_rank" value="Target rank" />
                            <x-checklist-description-help :text="$fieldHelp['target_rank']['description'] ?? null" :examples="$fieldHelp['target_rank']['examples'] ?? []" />
                        </div>
                        <select id="target_rank" wire:model="targetRank" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($rankOptions as $rank)
                                <option value="{{ $rank }}">{{ $rank }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="plan_name" value="Plan name" />
                        <x-checklist-description-help :text="$fieldHelp['plan_name']['description'] ?? null" :examples="$fieldHelp['plan_name']['examples'] ?? []" />
                    </div>
                    <x-text-input id="plan_name" wire:model="planName" class="mt-1 block w-full" />
                </div>
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="deadline_at" value="Deadline" />
                        <x-checklist-description-help :text="$fieldHelp['plan_deadline']['description'] ?? null" :examples="$fieldHelp['plan_deadline']['examples'] ?? []" />
                    </div>
                    <x-text-input id="deadline_at" type="date" wire:model="deadlineAt" class="mt-1 block w-full" />
                </div>
            </div>

            <button type="button" wire:click="calculateFunnel" wire:loading.attr="disabled" class="mt-6 rounded-lg bg-gw-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                <span wire:loading.remove wire:target="calculateFunnel">Calculate activity funnel</span>
                <span wire:loading wire:target="calculateFunnel">Calculating…</span>
            </button>
        @else
            <div class="mb-6 rounded-xl border border-gw-accent/30 bg-gw-accent-soft/50 p-4">
                <p class="text-sm font-semibold text-gw-primary">{{ $planningTypes[$planningType]['label'] ?? '' }} — {{ number_format((float) $targetValue, 0) }}</p>
                <p class="mt-1 text-xs text-slate-600">Review the reverse-engineered funnel before creating linked goals.</p>
            </div>

            <div class="relative space-y-0">
                @foreach ($previewFunnel as $index => $stage)
                    <div wire:key="funnel-{{ $stage['key'] }}" class="relative flex gap-4 pb-6">
                        @if (! $loop->last)
                            <div class="absolute left-[1.1rem] top-10 h-full w-0.5 bg-gw-accent/40"></div>
                        @endif
                        <div class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">{{ $index + 1 }}</div>
                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gw-accent">{{ ucfirst($stage['goal_type'] ?? 'outcome') }} goal</p>
                                    <h3 class="text-base font-semibold text-gw-primary">{{ $stage['label'] }}</h3>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ ucfirst($stage['hierarchy_level'] ?? 'annual') }}</span>
                            </div>
                            <div class="mt-3 grid gap-2 text-xs sm:grid-cols-4">
                                <div class="rounded-lg bg-slate-50 px-2 py-1.5"><span class="text-slate-500">Annual</span><p class="font-semibold text-gw-primary">{{ number_format($stage['annual_target'] ?? 0, 0) }}</p></div>
                                <div class="rounded-lg bg-slate-50 px-2 py-1.5"><span class="text-slate-500">Monthly</span><p class="font-semibold text-gw-primary">{{ number_format($stage['monthly_target'] ?? 0, 0) }}</p></div>
                                <div class="rounded-lg bg-slate-50 px-2 py-1.5"><span class="text-slate-500">Weekly</span><p class="font-semibold text-gw-primary">{{ number_format($stage['weekly_target'] ?? 0, 0) }}</p></div>
                                <div class="rounded-lg bg-slate-50 px-2 py-1.5"><span class="text-slate-500">Daily</span><p class="font-semibold text-gw-primary">{{ number_format($stage['daily_target'] ?? 0, 1) }}</p></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                <button type="button" wire:click="goToStep(1)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</button>
                <button type="button" wire:click="createPlan" class="rounded-lg bg-gw-accent px-5 py-2.5 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Create Success Blueprint</button>
            </div>
        @endif
    </div>
</div>
