<x-app-layout>
    <section class="space-y-6">
        <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-[#05070B] via-[#111827] to-[#2A2110] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Team</p>
                    <h1 class="mt-2 text-2xl font-semibold">All Downlines</h1>
                    <p class="mt-2 text-sm text-slate-300">Search, filter, and review every member in your visible downline.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('team.tree') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Tree</a>
                    <a href="{{ route('team.hierarchy') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Hierarchy</a>
                    <a href="{{ route('team.org-chart') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Org Chart</a>
                    @if (auth()->user()->hasAnyPermission(['export team data', 'view all teams']))
                        <a href="{{ route('team.export', request()->query()) }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Export CSV</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                @foreach ([
                    ['label' => 'Total Downlines', 'value' => $stats['total'], 'theme' => 'navy', 'subtitle' => 'Visible members in your team'],
                    ['label' => 'Active', 'value' => $stats['active'], 'theme' => 'emerald', 'subtitle' => 'Currently active members'],
                    ['label' => 'Licensed', 'value' => $stats['licensed'], 'theme' => 'gold', 'subtitle' => 'Licensed producers'],
                    ['label' => 'CFM Assigned', 'value' => $stats['cfm_assigned'], 'theme' => 'cyan', 'subtitle' => 'With mentor coverage'],
                    ['label' => 'New This Month', 'value' => $stats['new_this_month'], 'theme' => 'violet', 'subtitle' => 'Recent recruits'],
                    ['label' => 'Building Teams', 'value' => $stats['with_downline'], 'theme' => 'amber', 'subtitle' => 'Members with their own recruits'],
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

        <form method="GET" class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..." class="h-10 rounded-lg border-slate-300 text-sm xl:col-span-2">
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
                <button class="h-10 rounded-lg bg-gw-primary px-4 text-sm font-semibold text-white">Apply</button>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input name="joined_from" type="date" value="{{ request('joined_from') }}" class="h-10 rounded-lg border-slate-300 text-sm">
                <input name="joined_to" type="date" value="{{ request('joined_to') }}" class="h-10 rounded-lg border-slate-300 text-sm">
                <a href="{{ route('team.downlines') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <div
            class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm"
            x-data="{ selectedMemberIds: [], pageMemberIds: @js($members->getCollection()->pluck('id')->values()), selectPage(event) { this.selectedMemberIds = event.target.checked ? [...this.pageMemberIds] : []; } }"
        >
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Members</h2>
                    <p class="text-sm text-slate-600">{{ $members->total() }} visible records</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <p class="font-semibold">The bulk assignment could not be submitted.</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($result = session('bulk_cfm_assignment_result'))
                <div class="mb-4 rounded-lg border p-4 text-sm {{ count($result['failed']) ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' }}" role="status">
                    @if (count($result['assigned']))
                        <p class="font-semibold">{{ count($result['assigned']) }} CFM assignment{{ count($result['assigned']) === 1 ? '' : 's' }} submitted for confirmation.</p>
                    @else
                        <p class="font-semibold">No CFM assignments were created.</p>
                    @endif
                    @if (count($result['failed']))
                        <p class="mt-2 font-semibold">{{ count($result['failed']) }} member{{ count($result['failed']) === 1 ? '' : 's' }} could not be assigned:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($result['failed'] as $memberId => $message)
                                <li>Member #{{ $memberId }}: {{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if ($cfmCandidates !== [])
                <form method="POST" action="{{ route('team.downlines.assign-cfm', request()->query()) }}" class="mb-4 flex flex-col gap-3 rounded-lg border border-gw-accent/40 bg-gw-accent-soft/50 p-4 sm:flex-row sm:items-end">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gw-primary">
                            <span x-text="selectedMemberIds.length"></span> selected
                            <span class="text-slate-600">· Assign CFM</span>
                        </p>
                        <label for="bulk-cfm-id" class="mt-2 block text-xs font-semibold uppercase tracking-wide text-slate-600">Certified Field Mentor</label>
                        <select id="bulk-cfm-id" name="cfm_id" required class="mt-1 h-10 w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Choose a CFM</option>
                            @foreach ($cfmCandidates as $cfm)
                                <option value="{{ $cfm['id'] }}">{{ $cfm['name'] }}{{ $cfm['rank'] ? ' · '.$cfm['rank'] : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" name="reason" maxlength="255" placeholder="Reason (optional)" class="h-10 rounded-lg border-slate-300 text-sm sm:w-56">
                    <button
                        type="submit"
                        x-bind:disabled="selectedMemberIds.length === 0"
                        x-on:click="if (selectedMemberIds.length === 0) $event.preventDefault()"
                        class="h-10 rounded-lg bg-gw-primary px-4 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Submit Assignments
                    </button>
                    <template x-for="memberId in selectedMemberIds" :key="`bulk-member-${memberId}`">
                        <input type="hidden" name="associate_ids[]" :value="memberId">
                    </template>
                </form>
            @endif

            <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white/90">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">
                                @if ($cfmCandidates !== [])
                                    <input
                                        type="checkbox"
                                        class="rounded border-slate-400"
                                        aria-label="Select all members on this page"
                                        x-bind:checked="pageMemberIds.length > 0 && selectedMemberIds.length === pageMemberIds.length"
                                        x-bind:indeterminate="selectedMemberIds.length > 0 && selectedMemberIds.length < pageMemberIds.length"
                                        x-on:change="selectPage($event)"
                                    >
                                @endif
                            </th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">Sponsor</th>
                            <th class="px-4 py-3">CFM</th>
                            <th class="px-4 py-3">Country</th>
                            <th class="px-4 py-3">Licensing</th>
                            <th class="px-4 py-3">Onboarding</th>
                            <th class="px-4 py-3">Training</th>
                            <th class="px-4 py-3">FAP</th>
                            <th class="px-4 py-3">Directs</th>
                            <th class="px-4 py-3">Total Team</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Activity</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($members as $member)
                            @php($hierarchy = app(App\Services\DownlineHierarchyService::class))
                            @php($metrics = $hierarchy->visibleMemberMetrics(auth()->user(), $member))
                            @php($progress = $hierarchy->progressSummary($member))
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($cfmCandidates !== [])
                                        <input type="checkbox" value="{{ $member->id }}" x-model.number="selectedMemberIds" class="rounded border-slate-400" aria-label="Select {{ $member->name }}">
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('team.member', $member) }}" class="font-semibold text-gw-primary hover:text-gw-accent-dark">{{ $member->name }}</a>
                                    <div class="text-xs text-slate-500">{{ $member->email }}</div>
                                </td>
                                <td class="px-4 py-3">@include('team.downline.partials.member-badge', ['member' => $member])</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->sponsor?->name ?? 'None' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->mentor?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->profile?->country ?? 'Global' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ \App\Support\ChecklistProgressDisplay::label($progress['licensing']) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ \App\Support\ChecklistProgressDisplay::label($progress['onboarding']) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $progress['training'] }}%</td>
                                <td class="px-4 py-3 text-slate-600">{{ \App\Support\ChecklistProgressDisplay::label($progress['apprenticeship']) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->direct_recruits_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $metrics['total_downline'] }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ $member->last_login_at?->diffForHumans() ?? 'No activity' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('team.member.profile', $member) }}" title="View member profile" class="gw-icon-btn"><span class="sr-only">View member profile</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                                        <a href="{{ route('team.member.tree', $member) }}" title="View genealogy" class="gw-icon-btn"><span class="sr-only">View genealogy</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"></path><path d="M5 8h14"></path><path d="M5 16h14"></path></svg></a>
                                        <a href="{{ route('team.member.org-chart', $member) }}" title="View org branch" class="gw-icon-btn"><span class="sr-only">View org branch</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6V3"></path><path d="M6 21v-3"></path><path d="M18 21v-3"></path><path d="M6 18h12"></path><path d="M12 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"></path></svg></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $members->links() }}</div>
        </div>
    </section>
</x-app-layout>
