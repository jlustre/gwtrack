<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['label' => 'Total Goals', 'value' => $summary['total'], 'theme' => 'navy', 'subtitle' => 'All goals in your portfolio'],
                ['label' => 'Active', 'value' => $summary['active'], 'theme' => 'cyan', 'subtitle' => 'Currently in progress'],
                ['label' => 'Completed', 'value' => $summary['completed'], 'theme' => 'emerald', 'subtitle' => 'Goals fully achieved'],
                ['label' => 'Off Track', 'value' => $summary['off_track'], 'theme' => 'amber', 'subtitle' => 'Needs attention or coaching'],
                ['label' => 'Completion %', 'value' => $summary['completion_percent'].'%', 'theme' => 'gold', 'subtitle' => 'Overall completion rate'],
                ['label' => 'Current Streak', 'value' => $summary['current_streak'].' days', 'theme' => 'violet', 'subtitle' => 'Consecutive days on pace'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm xl:col-span-2">
            <h2 class="text-lg font-semibold text-gw-primary">Monthly Progress</h2>
            <div class="mt-6 flex h-40 items-end gap-3">
                @foreach ($summary['monthly_trend'] as $month)
                    <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                        <div class="flex h-32 w-full items-end justify-center gap-1">
                            <div class="w-3 rounded-t bg-gw-accent/80" style="height: {{ max(4, ($month['created'] / $trendMax) * 100) }}%"></div>
                            <div class="w-3 rounded-t bg-gw-primary" style="height: {{ max(4, ($month['completed'] / $trendMax) * 100) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">{{ $month['month'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex gap-4 text-xs text-slate-600">
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-gw-accent"></span> Created</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-gw-primary"></span> Completed</span>
            </div>
        </div>

        <div class="rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-accent-soft to-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">AI Coaching</h2>
            <p class="mt-1 text-xs text-slate-600">Future-ready performance suggestions</p>
            <ul class="mt-4 space-y-3">
                @forelse ($summary['ai_suggestions'] as $suggestion)
                    <li class="rounded-lg border border-gw-accent/20 bg-white/80 px-3 py-2 text-sm text-gw-primary">{{ $suggestion }}</li>
                @empty
                    <li class="text-sm text-slate-600">You're on pace. Keep building momentum.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Progress by Category</h2>
            <div class="mt-4 space-y-3">
                @foreach ($summary['by_category'] as $category)
                    @if ($category['count'] > 0)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-gw-primary">{{ $category['name'] }}</span>
                                <span class="text-slate-600">{{ $category['avg_progress'] }}%</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $category['avg_progress'] }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Recent Achievements</h2>
            @if ($summary['achievements']->isEmpty())
                <p class="mt-4 text-sm text-slate-600">Earn badges as you hit milestones.</p>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($summary['achievements'] as $achievement)
                        <li class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <span class="rounded-full bg-gw-accent/20 px-2 py-1 text-xs font-bold uppercase text-gw-accent-dark">{{ $achievement->badge->level }}</span>
                            <div>
                                <p class="text-sm font-semibold text-gw-primary">{{ $achievement->badge->name }}</p>
                                <p class="text-xs text-slate-500">{{ $achievement->earned_at?->format('M j, Y') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
