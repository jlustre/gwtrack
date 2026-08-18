<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Step {{ $step }} of 9</p>
                <h2 class="text-lg font-semibold text-gw-primary">
                    @switch($step)
                        @case(1) Select Goal Category @break
                        @case(2) Goal Name @break
                        @case(3) Target Value @break
                        @case(4) Measurement Type @break
                        @case(5) Deadline @break
                        @case(6) Milestones @break
                        @case(7) Accountability Partner @break
                        @case(8) Notifications @break
                        @default Review & Create @break
                    @endswitch
                </h2>
            </div>
            <div class="rounded-full border border-gw-accent/40 bg-gw-accent-soft px-3 py-1 text-sm font-semibold text-gw-accent-dark">
                SMART {{ $smartScore }}%
            </div>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200">
            <div class="h-1.5 rounded-full bg-gw-accent transition-all" style="width: {{ round(($step / 9) * 100) }}%"></div>
        </div>
    </div>

    <div class="grid gap-6 overflow-visible p-6 lg:grid-cols-[minmax(0,1fr)_240px]">
        <div class="space-y-5 overflow-visible">
            @if ($step === 1)
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory({{ $category->id }})"
                            wire:key="goal-category-{{ $category->id }}"
                            @class([
                                'rounded-xl border p-4 text-left transition',
                                'border-gw-accent bg-gw-accent-soft ring-2 ring-gw-accent/30' => (int) $goalCategoryId === $category->id,
                                'border-slate-200 hover:border-gw-accent/50' => (int) $goalCategoryId !== $category->id,
                            ])
                        >
                            <p class="font-semibold text-gw-primary">{{ $category->name }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ $category->description }}</p>
                        </button>
                    @endforeach
                </div>
                @if ($templates->isNotEmpty())
                    <div>
                        <p class="text-sm font-semibold text-gw-primary">Quick templates</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($templates as $template)
                                <button type="button" wire:click="applyTemplate({{ $template->id }})" class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-gw-accent">
                                    {{ $template->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
                <x-input-error :messages="$errors->get('goalCategoryId')" />
            @elseif ($step === 2)
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="goal_name" value="Goal name" />
                        <x-checklist-description-help :text="$fieldHelp['name']['description'] ?? null" :examples="$fieldHelp['name']['examples'] ?? []" />
                    </div>
                    <x-text-input id="goal_name" wire:model.live.debounce.500ms="name" class="mt-1 block w-full" placeholder="e.g. Recruit 4 associates this month" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="goal_description" value="Description" />
                        <x-checklist-description-help :text="$fieldHelp['description']['description'] ?? null" :examples="$fieldHelp['description']['examples'] ?? []" />
                    </div>
                    <textarea id="goal_description" wire:model.live.debounce.500ms="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="What does success look like?"></textarea>
                </div>
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="hierarchy_level" value="Goal level" />
                        <x-checklist-description-help :text="$fieldHelp['hierarchy_level']['description'] ?? null" :examples="$fieldHelp['hierarchy_level']['examples'] ?? []" />
                    </div>
                    <select id="hierarchy_level" wire:model="hierarchyLevel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($hierarchyLevels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($parentGoals->isNotEmpty())
                    <div class="overflow-visible">
                        <div class="flex items-center gap-2">
                            <x-input-label for="parent_goal" value="Link to parent goal (optional)" />
                            <x-checklist-description-help :text="$fieldHelp['parent_goal']['description'] ?? null" :examples="$fieldHelp['parent_goal']['examples'] ?? []" />
                        </div>
                        <select id="parent_goal" wire:model="parentGoalId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">None — standalone goal</option>
                            @foreach ($parentGoals as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }} ({{ ucfirst($parent->hierarchy_level) }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @elseif ($step === 3)
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="target_value" value="Target value" />
                        <x-checklist-description-help :text="$fieldHelp['target_value']['description'] ?? null" :examples="$fieldHelp['target_value']['examples'] ?? []" />
                    </div>
                    <x-text-input id="target_value" type="number" step="0.01" wire:model="targetValue" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('targetValue')" />
                </div>
                @if (count($metrics) > 0)
                    <div class="overflow-visible">
                        <div class="flex items-center gap-2">
                            <x-input-label for="metric_key" value="Automated KPI (optional)" />
                            <x-checklist-description-help :text="$fieldHelp['metric_key']['description'] ?? null" :examples="$fieldHelp['metric_key']['examples'] ?? []" />
                        </div>
                        <select id="metric_key" wire:model="metricKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Manual tracking</option>
                            @foreach ($metrics as $key => $metric)
                                <option value="{{ $key }}">{{ $metric['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @elseif ($step === 4)
                <div class="mb-2 flex items-center gap-2">
                    <p class="text-sm font-semibold text-gw-primary">Measurement type</p>
                    <x-checklist-description-help :text="$fieldHelp['measurement_type']['description'] ?? null" :examples="$fieldHelp['measurement_type']['examples'] ?? []" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($measurementTypes as $key => $label)
                        <button type="button" wire:click="selectMeasurementType('{{ $key }}')" @class([
                            'rounded-lg border px-4 py-3 text-left text-sm font-semibold',
                            'border-gw-accent bg-gw-accent-soft text-gw-primary' => $measurementType === $key,
                            'border-slate-200 text-slate-700' => $measurementType !== $key,
                        ])>{{ $label }}</button>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('measurementType')" />
            @elseif ($step === 5)
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="overflow-visible">
                        <div class="flex items-center gap-2">
                            <x-input-label for="starts_at" value="Start date" />
                            <x-checklist-description-help :text="$fieldHelp['starts_at']['description'] ?? null" :examples="$fieldHelp['starts_at']['examples'] ?? []" />
                        </div>
                        <x-text-input id="starts_at" type="date" wire:model="startsAt" class="mt-1 block w-full" />
                    </div>
                    <div class="overflow-visible">
                        <div class="flex items-center gap-2">
                            <x-input-label for="deadline_at" value="Deadline" />
                            <x-checklist-description-help :text="$fieldHelp['deadline_at']['description'] ?? null" :examples="$fieldHelp['deadline_at']['examples'] ?? []" />
                        </div>
                        <x-text-input id="deadline_at" type="date" wire:model="deadlineAt" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('deadlineAt')" />
                    </div>
                </div>
            @elseif ($step === 6)
                <div class="mb-2 flex items-center gap-2">
                    <p class="text-sm font-semibold text-gw-primary">Milestones</p>
                    <x-checklist-description-help :text="$fieldHelp['milestones']['description'] ?? null" :examples="$fieldHelp['milestones']['examples'] ?? []" />
                </div>
                <div class="space-y-3">
                    @foreach ($milestones as $index => $milestone)
                        <div wire:key="milestone-{{ $index }}" class="grid gap-2 rounded-lg border border-slate-200 p-3 md:grid-cols-[1fr_140px_100px_auto]">
                            <x-text-input wire:model="milestones.{{ $index }}.name" placeholder="Milestone name" />
                            <x-text-input type="date" wire:model="milestones.{{ $index }}.due_at" />
                            <x-text-input type="number" wire:model="milestones.{{ $index }}.target_value" placeholder="Target" />
                            <button type="button" wire:click="removeMilestone({{ $index }})" class="text-xs font-semibold text-red-600">Remove</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addMilestone" class="text-sm font-semibold text-gw-accent-dark hover:underline">+ Add milestone</button>
                </div>
            @elseif ($step === 7)
                <div class="overflow-visible">
                    <div class="flex items-center gap-2">
                        <x-input-label for="accountability_partner" value="Accountability partner" />
                        <x-checklist-description-help :text="$fieldHelp['accountability_partner']['description'] ?? null" :examples="$fieldHelp['accountability_partner']['examples'] ?? []" />
                    </div>
                    <select id="accountability_partner" wire:model="accountabilityPartnerId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        <option value="">None selected</option>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($step === 8)
                <div class="mb-2 flex items-center gap-2">
                    <p class="text-sm font-semibold text-gw-primary">Notifications</p>
                    <x-checklist-description-help :text="$fieldHelp['notifications']['description'] ?? null" :examples="$fieldHelp['notifications']['examples'] ?? []" />
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-sm text-gw-primary"><input type="checkbox" wire:model="notifyEmail" class="rounded border-slate-300 text-gw-accent"> Email progress updates</label>
                    <label class="flex items-center gap-2 text-sm text-gw-primary"><input type="checkbox" wire:model="notifyInApp" class="rounded border-slate-300 text-gw-accent"> In-app notifications</label>
                    <label class="flex items-center gap-2 text-sm text-gw-primary"><input type="checkbox" wire:model="remindWeekly" class="rounded border-slate-300 text-gw-accent"> Weekly check-in reminder</label>
                </div>
            @else
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs uppercase text-slate-500">Category</dt><dd class="mt-1 font-semibold text-gw-primary">{{ $categories->firstWhere('id', $goalCategoryId)?->name }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs uppercase text-slate-500">Goal</dt><dd class="mt-1 font-semibold text-gw-primary">{{ $name }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs uppercase text-slate-500">Target</dt><dd class="mt-1 font-semibold text-gw-primary">{{ $targetValue }} ({{ $measurementTypes[$measurementType] ?? $measurementType }})</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs uppercase text-slate-500">Deadline</dt><dd class="mt-1 font-semibold text-gw-primary">{{ $deadlineAt ?: '—' }}</dd></div>
                </dl>
            @endif

            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                @if ($step > 1)
                    <button type="button" wire:click="previousStep" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</button>
                @endif
                @if ($step < 9)
                    <button type="button" wire:click="nextStep" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Continue</button>
                @else
                    <button type="button" wire:click="save" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Create Goal</button>
                @endif
            </div>
        </div>

        <aside class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">SMART validation</p>
            <div class="mt-3 space-y-2">
                @foreach ($smartFeedback as $item)
                    <div class="flex items-start gap-2 text-sm">
                        <span @class([
                            'mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            'bg-emerald-100 text-emerald-700' => $item['passed'],
                            'bg-amber-100 text-amber-800' => ! $item['passed'],
                        ])>{{ $item['passed'] ? '✓' : '!' }}</span>
                        <div>
                            <p class="font-semibold text-gw-primary">{{ $item['label'] }}</p>
                            @if (! $item['passed'] && $item['suggestion'])
                                <p class="text-xs text-slate-600">{{ $item['suggestion'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>
