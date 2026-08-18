<div class="space-y-6">
    @php
        $profile = $hub['profile'];
        $levelColors = [
            'bronze' => 'border-amber-300 bg-amber-50 text-amber-900',
            'silver' => 'border-slate-300 bg-slate-100 text-slate-800',
            'gold' => 'border-gw-accent/50 bg-gw-accent-soft text-gw-accent-dark',
            'platinum' => 'border-violet-300 bg-violet-50 text-violet-900',
            'diamond' => 'border-sky-300 bg-sky-50 text-sky-900',
        ];
    @endphp

    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                <h1 class="mt-2 text-3xl font-semibold">Achievements & Leaderboard</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">Earn badges, build learning streaks, and climb the academy leaderboard.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Academy Points</p>
            <p class="mt-2 text-3xl font-bold text-gw-primary">{{ number_format($profile->total_points) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Current Streak</p>
            <p class="mt-2 text-3xl font-bold text-orange-600">{{ $profile->current_streak }}<span class="ml-1 text-base font-semibold text-slate-500">days</span></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Best Streak</p>
            <p class="mt-2 text-3xl font-bold text-gw-accent-dark">{{ $profile->longest_streak }}<span class="ml-1 text-base font-semibold text-slate-500">days</span></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Badges Earned</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $hub['badges']->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Leaderboard Rank</p>
            <p class="mt-2 text-3xl font-bold text-gw-primary">{{ $hub['rank'] ? '#'.$hub['rank'] : '—' }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <h2 class="text-lg font-semibold text-gw-primary">Your Badges</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse ($hub['badges'] as $userBadge)
                    @php $badge = $userBadge->badge; @endphp
                    <div class="rounded-lg border px-4 py-3 {{ $levelColors[$badge->level] ?? $levelColors['bronze'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $badge->name }}</p>
                                <p class="mt-1 text-xs opacity-80">{{ $badge->description }}</p>
                                <p class="mt-2 text-[0.65rem] font-semibold uppercase tracking-wide">{{ config('training-academy.badge_levels.'.$badge->level, $badge->level) }} · {{ $badge->points }} pts</p>
                            </div>
                            <span class="text-[0.65rem] font-medium opacity-70">{{ $userBadge->earned_at?->format('M j, Y') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600 sm:col-span-2">Complete lessons, courses, and certifications to earn your first badge.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gw-primary">Leaderboard</h2>
                @if (auth()->user()->team_id)
                    <div class="flex rounded-lg border border-slate-200 p-0.5 text-xs">
                        <button type="button" wire:click="$set('leaderboardScope', 'organization')" @class(['rounded-md px-2.5 py-1 font-semibold', 'bg-gw-primary text-white' => $leaderboardScope === 'organization', 'text-slate-600' => $leaderboardScope !== 'organization'])>All</button>
                        <button type="button" wire:click="$set('leaderboardScope', 'team')" @class(['rounded-md px-2.5 py-1 font-semibold', 'bg-gw-primary text-white' => $leaderboardScope === 'team', 'text-slate-600' => $leaderboardScope !== 'team'])>My Team</button>
                    </div>
                @endif
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($leaderboard as $row)
                    <div @class([
                        'flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm',
                        'border-gw-accent/40 bg-gw-accent-soft' => $row['user']->id === auth()->id(),
                        'border-slate-100 bg-slate-50/80' => $row['user']->id !== auth()->id(),
                    ])>
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">#{{ $row['rank'] }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gw-primary">{{ $row['user']->name }}</p>
                                <p class="text-xs text-slate-500">{{ $row['badge_count'] }} badges · {{ $row['streak'] }} day streak</p>
                            </div>
                        </div>
                        <span class="shrink-0 font-bold text-gw-accent-dark">{{ number_format($row['points']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Leaderboard rankings appear once members start earning academy points.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">Available Badges</h2>
        <p class="mt-1 text-xs text-slate-500">Keep learning to unlock these achievements</p>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($hub['available_badges'] as $badge)
                @php $earned = $hub['earned_badge_ids']->contains($badge->id); @endphp
                <div @class([
                    'rounded-lg border px-4 py-3',
                    $earned ? ($levelColors[$badge->level] ?? $levelColors['bronze']) : 'border-slate-200 bg-slate-50 text-slate-600 opacity-80',
                ])>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold {{ $earned ? '' : 'text-slate-700' }}">{{ $badge->name }}</p>
                            <p class="mt-1 text-xs">{{ $badge->description }}</p>
                        </div>
                        @if ($earned)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-emerald-800">Earned</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-slate-600">Locked</span>
                        @endif
                    </div>
                    <p class="mt-2 text-[0.65rem] font-semibold uppercase tracking-wide">{{ config('training-academy.badge_levels.'.$badge->level, $badge->level) }} · {{ $badge->points }} pts</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
