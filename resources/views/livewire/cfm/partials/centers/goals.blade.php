@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Performance planning</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $center['coaching_url'] }}" class="inline-flex rounded-lg bg-gw-primary px-3 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Goals coaching</a>
                <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">View profile</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Active goals', 'value' => $center['stats']['active'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Avg progress', 'value' => ($center['stats']['avg_progress'] ?? 0).'%', 'theme' => 'gold'],
            ['label' => 'Off track', 'value' => $center['stats']['off_track'] ?? 0, 'theme' => 'red'],
            ['label' => 'Completed', 'value' => $center['stats']['completed'] ?? 0, 'theme' => 'emerald'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Current goals</h3>
            @if (count($center['goals']) === 0)
                <p class="mt-4 text-sm text-slate-500">This trainee has not created goals yet.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($center['goals'] as $goal)
                        <article wire:key="goal-{{ $goal['id'] }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gw-accent">{{ $goal['category'] }}</p>
                                    <h4 class="mt-1 font-semibold text-gw-primary">{{ $goal['name'] }}</h4>
                                    <p class="mt-1 text-xs text-slate-500">Target {{ $goal['target'] }} · Actual {{ $goal['actual'] }} · Due {{ $goal['deadline'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gw-accent-dark">{{ $goal['progress'] }}%</p>
                                    <span @class([
                                        'mt-1 inline-flex rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                        'bg-red-100 text-red-800' => $goal['pace'] === 'off_track' || $goal['pace'] === 'behind',
                                        'bg-emerald-100 text-emerald-800' => $goal['pace'] === 'ahead',
                                        'bg-sky-100 text-sky-800' => $goal['pace'] === 'on_track',
                                    ])>{{ str_replace('_', ' ', $goal['pace']) }}</span>
                                </div>
                            </div>
                            <div class="mt-3 h-1.5 w-full rounded-full bg-slate-200">
                                <div class="h-1.5 rounded-full bg-gw-accent" style="width: {{ min(100, (int) $goal['progress']) }}%"></div>
                            </div>
                            @if ($goal['milestones_total'] > 0)
                                <p class="mt-2 text-xs text-slate-500">{{ $goal['milestones_complete'] }}/{{ $goal['milestones_total'] }} milestones complete</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">By category</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($center['category_breakdown'] as $row)
                        <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <span class="text-slate-700">{{ $row['category'] }}</span>
                            <span class="font-semibold text-gw-primary">{{ $row['avg_progress'] }}%</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No goal categories yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5">
                <h3 class="text-sm font-semibold text-gw-primary">Coaching suggestions</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    @forelse ($center['suggestions'] as $suggestion)
                        <li>{{ $suggestion }}</li>
                    @empty
                        <li>Goals look on track. Reinforce momentum in your next touchpoint.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
