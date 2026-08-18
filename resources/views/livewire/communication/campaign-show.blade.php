<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('communications.campaigns.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Campaign Center</a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $typeMeta['label'] ?? ucfirst($campaign->type) }}</p>
        <h1 class="text-3xl font-semibold text-gw-primary">{{ $campaign->name }}</h1>
        @if ($campaign->description)
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $campaign->description }}</p>
        @endif
    </div>

    @if (session('communication_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('communication_status') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-gw-primary">Leaderboard</h2>
                @unless ($participant)
                    <button type="button" wire:click="join" class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-gw-accent">Join campaign</button>
                @endunless
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="px-2 py-2">Rank</th>
                            <th class="px-2 py-2">Associate</th>
                            <th class="px-2 py-2 text-right">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaderboard as $row)
                            <tr @class(['border-b border-slate-100', 'bg-gw-accent-soft' => $participant && $participant->user_id === $row['user_id']])>
                                <td class="px-2 py-2 font-semibold text-gw-accent-dark">#{{ $row['rank'] }}</td>
                                <td class="px-2 py-2 font-medium text-gw-primary">{{ $row['name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ number_format($row['progress_value'], $row['metric'] === 'production' ? 0 : 0) }} {{ $typeMeta['unit'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-2 py-6 text-center text-slate-500">No participants yet. Be the first to join.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($participant)
                <p class="mt-3 text-sm text-slate-600">Your progress: <strong>{{ number_format($participant->progress_value, 0) }}</strong> {{ $typeMeta['unit'] ?? '' }}</p>
            @endif
        </section>

        <aside class="space-y-4">
            @if ($campaign->rules)
                <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Rules</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $campaign->rules }}</p>
                </section>
            @endif
            @if (! empty($campaign->prizes))
                <section class="rounded-2xl border border-gw-accent/30 bg-gw-accent-soft p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Prizes</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        @foreach ($campaign->prizes as $prize)
                            <li>• {{ $prize }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>

    @if ($campaign->announcements->isNotEmpty())
        <section class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Campaign updates</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($campaign->announcements as $announcement)
                    <li>
                        <a href="{{ route('communications.show', $announcement) }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">{{ $announcement->title }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
