<div class="grid gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 space-y-4">
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('selectedTraineeId', null)" @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-gw-primary text-white' => ! $selectedTraineeId, 'border border-slate-300 text-slate-600' => $selectedTraineeId])>All trainees</button>
            @foreach ($trainees as $trainee)
                <button type="button" wire:click="$set('selectedTraineeId', {{ $trainee->id }})" @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-gw-primary text-white' => $selectedTraineeId === $trainee->id, 'border border-slate-300 text-slate-600' => $selectedTraineeId !== $trainee->id])>{{ $trainee->name }}</button>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($traineeGoals as $goal)
                <article wire:key="trainee-goal-{{ $goal->id }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gw-accent">{{ $goal->user?->name }}</p>
                            <h3 class="mt-1 text-base font-semibold text-gw-primary">{{ $goal->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $goal->category?->name }} &middot; {{ $goal->progressPercent() }}% complete</p>
                        @if ($traineeInsights->get($goal->user_id))
                            @php($insight = $traineeInsights->get($goal->user_id))
                            @if (($insight['alerts'] ?? collect())->isNotEmpty())
                                <p class="mt-2 text-xs font-semibold text-amber-700">{{ $insight['alerts']->first()->title }}</p>
                            @endif
                        @endif
                        </div>
                        <button type="button" wire:click="$set('selectedGoalId', {{ $goal->id }})" class="rounded-md border border-gw-accent px-3 py-1 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-soft">Add coach note</button>
                    </div>
                    @if ($goal->milestones->isNotEmpty())
                        <ul class="mt-3 space-y-1 text-xs text-slate-600">
                            @foreach ($goal->milestones as $milestone)
                                <li>{{ $milestone->name }} @if($milestone->isComplete())<span class="text-emerald-600">(done)</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">No trainee goals to review yet.</p>
            @endforelse
        </div>
    </div>

    <aside class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gw-primary">Coach note</h3>
            <form wire:submit="saveCoachNote" class="mt-3 space-y-3">
                <select wire:model="selectedGoalId" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select goal</option>
                    @foreach ($traineeGoals as $goal)
                        <option value="{{ $goal->id }}">{{ $goal->user?->name }} — {{ $goal->name }}</option>
                    @endforeach
                </select>
                <textarea wire:model="coachNote" rows="5" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Recommendations, action plan, encouragement…"></textarea>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Voice note (optional)</label>
                    <input type="file" wire:model="coachAudio" accept="audio/*" class="mt-1 block w-full text-xs text-slate-600">
                    <x-input-error :messages="$errors->get('coachAudio')" />
                </div>
                <x-input-error :messages="$errors->get('coachNote')" />
                <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save note</button>
            </form>
        </div>

        <div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-accent-soft to-white p-5">
            <h3 class="text-sm font-semibold text-gw-primary">AI coaching suggestions</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @forelse ($suggestions as $suggestion)
                    <li>{{ $suggestion }}</li>
                @empty
                    <li>Trainee goals are on track.</li>
                @endforelse
            </ul>
        </div>

        @if ($selectedTraineeId && $traineeInsights->has($selectedTraineeId))
            @php($insight = $traineeInsights->get($selectedTraineeId))
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gw-primary">Conversion KPIs</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    @forelse ($insight['conversion_kpis'] ?? [] as $kpi)
                        <li>{{ $kpi['label'] }}: {{ $kpi['rate'] }}%</li>
                    @empty
                        <li>Building conversion history…</li>
                    @endforelse
                </ul>
            </div>
            @if (($insight['alerts'] ?? collect())->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <h3 class="text-sm font-semibold text-amber-900">Deficiencies</h3>
                    <ul class="mt-2 space-y-1 text-sm text-amber-800">
                        @foreach ($insight['alerts'] as $alert)
                            <li>{{ $alert->message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </aside>
</div>
