<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-[#05070B] shadow-sm">
            <div class="bg-gradient-to-br from-[#05070B] via-[#111827] to-[#2A2110] px-6 py-7 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Downline Management</p>
                <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Team Command Center</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                            View hierarchy, leadership depth, rank movement, licensing readiness, mentorship coverage, and team growth from one protected workspace.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('team.production') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Production</a>
                        <a href="{{ route('team.recruiting.index') }}" class="rounded-lg border border-emerald-400 bg-emerald-400 px-4 py-2 text-sm font-semibold text-gw-primary">Recruiting</a>
                        <a href="{{ route('team.tree') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Tree View</a>
                        <a href="{{ route('team.hierarchy') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Hierarchy Table</a>
                        <a href="{{ route('team.org-chart') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Org Chart</a>
                        <a href="{{ route('team.downlines') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">All Downlines</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Team', 'value' => $stats['total_team'], 'theme' => 'navy', 'subtitle' => 'Members in your downline'],
                    ['label' => 'Direct Recruits', 'value' => $stats['direct_recruits'], 'theme' => 'cyan', 'subtitle' => 'First-level recruits'],
                    ['label' => 'Active Associates', 'value' => $stats['active_associates'], 'theme' => 'emerald', 'subtitle' => 'Currently active members'],
                    ['label' => 'Licensed Associates', 'value' => $stats['licensed_associates'], 'theme' => 'gold', 'subtitle' => 'Fully licensed producers'],
                    ['label' => 'New This Month', 'value' => $stats['new_this_month'], 'theme' => 'violet', 'subtitle' => 'Recent team growth'],
                    ['label' => 'Pending Licensing', 'value' => $stats['pending_licensing'], 'theme' => 'amber', 'subtitle' => 'Awaiting licensure'],
                    ['label' => 'CFM Assigned', 'value' => $stats['cfm_assigned'], 'theme' => 'slate', 'subtitle' => 'With mentor coverage'],
                    ['label' => 'Training Average', 'value' => $stats['training_average'].'%', 'theme' => 'cyan', 'subtitle' => 'Team training completion'],
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

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Rank Distribution</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($rankDistribution as $rank => $total)
                        <div class="flex items-center justify-between rounded-lg border border-slate-300 bg-white/85 px-4 py-3">
                            <span class="font-semibold text-gw-primary">{{ $rank }}</span>
                            <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-1 text-xs font-bold">{{ $total }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No rank data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Country Distribution</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($countryDistribution as $country => $total)
                        <div class="flex items-center justify-between rounded-lg border border-slate-300 bg-white/85 px-4 py-3">
                            <span class="font-semibold text-gw-primary">{{ $country }}</span>
                            <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-1 text-xs font-bold">{{ $total }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No country data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Visible Team Members</h2>
                    <p class="mt-1 text-sm text-slate-600">Privacy-filtered preview of members you are allowed to see.</p>
                </div>
                <a href="{{ route('team.downlines', request()->query()) }}" class="rounded-lg border border-gw-accent bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-soft">Open All Downlines</a>
            </div>

            <form method="GET" action="{{ route('team.index') }}" class="mt-5 space-y-3 rounded-lg border border-slate-300 bg-white/85 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name, email, phone..."
                        class="h-10 rounded-lg border-slate-300 text-sm xl:col-span-2"
                    >
                    <select name="rank_id" class="h-10 rounded-lg border-slate-300 text-sm">
                        <option value="">All Ranks</option>
                        @foreach ($filters['ranks'] as $rank)
                            <option value="{{ $rank->id }}" @selected((string) request('rank_id') === (string) $rank->id)>{{ $rank->code }}</option>
                        @endforeach
                    </select>
                    <select name="country" class="h-10 rounded-lg border-slate-300 text-sm">
                        <option value="">All Countries</option>
                        @foreach ($filters['countries'] as $country)
                            <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="h-10 rounded-lg border-slate-300 text-sm">
                        <option value="">Any Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                    <button type="submit" class="h-10 rounded-lg bg-gw-primary px-4 text-sm font-semibold text-white">Apply</button>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <input name="joined_from" type="date" value="{{ request('joined_from') }}" class="h-10 rounded-lg border-slate-300 text-sm" aria-label="Joined from">
                    <input name="joined_to" type="date" value="{{ request('joined_to') }}" class="h-10 rounded-lg border-slate-300 text-sm" aria-label="Joined to">
                    <a href="{{ route('team.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 xl:col-span-2">Reset Filters</a>
                </div>
            </form>

            <p class="mt-4 text-sm text-slate-600">{{ $members->total() }} matching members</p>

            <div class="mt-3 overflow-x-auto rounded-lg border border-slate-300 bg-white/85">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">Sponsor</th>
                            <th class="px-4 py-3">CFM</th>
                            <th class="px-4 py-3">Directs</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($members as $member)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gw-primary">{{ $member->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $member->profile?->city ?? 'City not set' }}
                                        @if ($member->profile?->country)
                                            &middot; {{ $member->profile->country }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">@include('team.downline.partials.member-badge', ['member' => $member])</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->sponsor?->name ?? 'None' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->mentor?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->direct_recruits_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <a
                                            href="{{ route('team.member.profile', $member) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gw-accent bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            View Profile
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No members match your search or filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $members->links() }}</div>
        </div>
    </section>
</x-app-layout>
