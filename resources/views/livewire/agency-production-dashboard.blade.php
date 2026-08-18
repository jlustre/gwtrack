<div class="space-y-6">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Production Analytics</p>
                    <h1 class="mt-2 text-2xl font-semibold">Production Dashboard</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        @if ($dashboard['show_team_scope'])
                            Leadership visibility into posted production across your agency — personal totals, team rollups, top producers, and monthly trends.
                        @elseif (! $dashboard['is_self'])
                            Posted production for <strong>{{ $dashboard['member']['name'] }}</strong>.
                        @else
                            Track your posted production entries, credited YTD totals, and monthly activity.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($dashboard['is_leadership_view'])
                        <a href="{{ $dashboard['team_dashboard_url'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                            Team command center
                        </a>
                    @endif
                    <a href="{{ $dashboard['profile_url'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        {{ $dashboard['is_self'] ? 'My profile' : 'Member profile' }}
                    </a>
                    @if (! $dashboard['is_self'])
                        <button type="button" wire:click="clearMemberFilter" class="rounded-lg border border-gw-accent bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary">
                            Back to agency view
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ($dashboard['periods'] as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('period', '{{ $key }}')"
                        class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $dashboard['period'] === $key ? 'bg-gw-primary text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <p class="text-sm text-slate-500">{{ $dashboard['range']['label'] }}</p>
        </div>

        @if ($dashboard['show_team_scope'] && count($dashboard['team_member_options']) > 1)
            <div class="border-b border-slate-100 px-6 py-4">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Drill into member</label>
                <select
                    wire:model.live="member"
                    class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                    <option value="">Agency overview</option>
                    @foreach ($dashboard['team_member_options'] as $option)
                        <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div @class([
            'grid gap-4 p-6 md:grid-cols-2',
            'xl:grid-cols-5' => $dashboard['show_team_scope'],
            'xl:grid-cols-3' => ! $dashboard['show_team_scope'],
        ])>
            @if ($dashboard['show_team_scope'])
                <x-tracker-stat-card label="Team production" :value="$dashboard['stats']['team_production_formatted']" theme="gold" />
                <x-tracker-stat-card label="Active producers" :value="$dashboard['stats']['active_producers'] ?? 0" theme="emerald" />
                <x-tracker-stat-card label="Team entries" :value="$dashboard['stats']['team_entry_count'] ?? 0" theme="cyan" />
            @endif
            <x-tracker-stat-card
                :label="$dashboard['is_self'] ? 'My posted production' : 'Member posted production'"
                :value="$dashboard['stats']['personal_production_formatted']"
                theme="navy"
            />
            <x-tracker-stat-card label="Credited YTD total" :value="$dashboard['stats']['credited_production_formatted']" theme="gold" />
            <x-tracker-stat-card label="Posted entries" :value="$dashboard['stats']['personal_entry_count']" theme="slate" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Monthly production trend</h2>
            <p class="mt-1 text-sm text-slate-500">Last {{ count($dashboard['monthly_trend']) }} months of posted annual premium.</p>
            <div class="mt-4 space-y-3">
                @php
                    $maxTrend = max(array_column($dashboard['monthly_trend'], 'total') ?: [0]);
                @endphp
                @forelse ($dashboard['monthly_trend'] as $point)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gw-primary">{{ $point['label'] }}</span>
                            <span class="text-slate-600">{{ $point['total_formatted'] }} · {{ $point['entry_count'] }} {{ str('entry')->plural($point['entry_count']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-gw-primary to-gw-accent"
                                style="width: {{ $maxTrend > 0 ? round(($point['total'] / $maxTrend) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No production entries in this trend window yet.</p>
                @endforelse
            </div>
        </div>

        @if ($dashboard['show_team_scope'])
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Top producers</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $dashboard['period_label'] }} leaders in your agency.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['top_producers'] as $index => $producer)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="font-semibold text-gw-primary">
                                    <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-gw-accent-muted text-xs font-bold text-gw-accent-dark">{{ $index + 1 }}</span>
                                    @if ($producer['drilldown_url'])
                                        <a href="{{ $producer['drilldown_url'] }}" class="hover:underline">{{ $producer['name'] }}</a>
                                    @else
                                        {{ $producer['name'] }}
                                    @endif
                                </p>
                                @if ($producer['rank'])
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $producer['rank'] }} · {{ $producer['entry_count'] }} {{ str('entry')->plural($producer['entry_count']) }}</p>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-emerald-700">{{ $producer['total_formatted'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No posted production for this period yet.</p>
                    @endforelse
                </div>
            </div>
        @else
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Recent entries</h2>
                <p class="mt-1 text-sm text-slate-500">Latest posted production in the selected period.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['recent_entries'] as $entry)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ $entry['description'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $entry['posted_at'] }} · {{ ucfirst($entry['source']) }}</p>
                                </div>
                                <span class="text-sm font-bold text-emerald-700">{{ $entry['annual_premium_formatted'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No entries recorded for this period.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @if ($dashboard['show_team_scope'])
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Team production breakdown</h2>
            <p class="mt-1 text-sm text-slate-500">Per-member posted production and credited YTD totals.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2">Member</th>
                            <th class="px-3 py-2">Rank</th>
                            <th class="px-3 py-2 text-right">Period posted</th>
                            <th class="px-3 py-2 text-right">Entries</th>
                            <th class="px-3 py-2 text-right">Credited YTD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($dashboard['member_breakdown'] as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 font-semibold text-gw-primary">
                                    <a href="{{ $row['drilldown_url'] }}" class="hover:underline">{{ $row['name'] }}</a>
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $row['rank'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-emerald-700">{{ $row['total_formatted'] }}</td>
                                <td class="px-3 py-3 text-right text-slate-600">{{ $row['entry_count'] }}</td>
                                <td class="px-3 py-3 text-right text-gw-accent-dark">{{ $row['credited_ytd_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-500">No team members in scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Recent team entries</h2>
            <div class="mt-4 space-y-3">
                @forelse ($dashboard['recent_entries'] as $entry)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <p class="font-semibold text-gw-primary">{{ $entry['description'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                <a href="{{ $entry['profile_url'] }}" class="font-semibold text-gw-primary hover:underline">{{ $entry['member_name'] }}</a>
                                · {{ $entry['posted_at'] }}
                            </p>
                        </div>
                        <span class="text-sm font-bold text-emerald-700">{{ $entry['annual_premium_formatted'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No team entries for this period.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
