@php
    $leaderExpandedDefaults = collect($leaders)->mapWithKeys(fn (array $leader) => [(string) $leader['id'] => false])->all();
@endphp

<x-app-layout>
    <section class="space-y-6">
        <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-[#05070B] via-[#111827] to-[#2A2110] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Organizational Chart</p>
                    <h1 class="mt-2 text-2xl font-semibold">Executive Team Structure</h1>
                    <p class="mt-2 text-sm text-slate-300">Leadership view for branch depth, team health, licensing, mentorship, and training summaries.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('team.tree') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Tree View</a>
                    <a href="{{ route('team.hierarchy') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Hierarchy Table</a>
                    <a href="{{ route('team.downlines') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">All Downlines</a>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ([
                    ['label' => 'Total Members', 'value' => $branchSummary['total_team'], 'theme' => 'navy', 'subtitle' => 'Across this branch'],
                    ['label' => 'Direct Recruits', 'value' => $branchSummary['direct_recruits'], 'theme' => 'cyan', 'subtitle' => 'First-level recruits'],
                    ['label' => 'Active Members', 'value' => $branchSummary['active_associates'], 'theme' => 'emerald', 'subtitle' => 'Currently active'],
                    ['label' => 'Licensed', 'value' => $branchSummary['licensed_associates'], 'theme' => 'gold', 'subtitle' => 'Licensed associates'],
                    ['label' => 'New This Month', 'value' => $branchSummary['new_this_month'], 'theme' => 'violet', 'subtitle' => 'Recent additions'],
                    ['label' => 'Training Avg', 'value' => $branchSummary['training_average'].'%', 'theme' => 'slate', 'subtitle' => 'Team training completion'],
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

        <div
            class="overflow-x-auto rounded-lg border border-slate-400 bg-white p-6 shadow-sm"
            x-data="orgChartBoard(@js($leaders), @js($leaderExpandedDefaults), @js($root))"
        >
            <div class="mb-5 space-y-4">
                <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="min-w-[200px] flex-1">
                        <label for="org-chart-search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input
                            id="org-chart-search"
                            type="search"
                            x-model="searchQuery"
                            placeholder="Search by name, email, rank, role, country, CFM…"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                    </div>

                    <template x-for="field in filterFields" :key="'org-chart-filter-' + field.key">
                        <div class="min-w-[140px] sm:w-auto">
                            <label :for="'org-chart-filter-' + field.key" class="block text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="field.label"></label>
                            <select
                                :id="'org-chart-filter-' + field.key"
                                x-model="filters[field.key]"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                                <template x-for="option in optionsForField(field)" :key="'org-chart-' + field.key + '-' + option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <button
                        type="button"
                        x-show="hasActiveFilters"
                        x-cloak
                        x-on:click="clearFilters()"
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Clear filters
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-slate-600">
                        <p>
                            Showing <span class="font-semibold text-gw-primary" x-text="filteredCount"></span>
                            of <span x-text="totalCount"></span> leader<span x-show="totalCount !== 1">s</span>
                            <span x-show="hasActiveFilters" x-cloak> (filtered)</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Cards are collapsed by default. Expand a card to view metrics and actions.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
                            x-on:click="expandAll()"
                        >
                            Expand All
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-gw-accent bg-gw-accent-muted px-3 py-2 text-xs font-bold text-gw-primary hover:bg-gw-accent-soft"
                            x-on:click="collapseAll()"
                        >
                            Collapse All
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-w-[960px]">
                <div class="flex justify-center">
                    <div class="w-80 rounded-lg border border-gw-accent bg-[#05070B] p-5 text-white shadow-lg">
                        <div class="flex items-center gap-3">
                            <x-user-avatar :photo-url="$root['profile_photo_url'] ?? null" :name="$root['name']" size="md" class="!h-12 !w-12 border-gw-accent bg-gw-accent-muted [&_span]:text-gw-primary" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold">{{ $root['name'] }}</div>
                                <div class="text-xs text-slate-300">{{ $root['rank_name'] }}</div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-1.5">
                                <button
                                    type="button"
                                    class="rounded-lg border border-white/25 bg-white/10 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-white hover:bg-white/20"
                                    x-on:click="openRootProfile()"
                                >
                                    View Profile
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gw-accent/60 bg-white/10 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-gw-accent hover:bg-white/15"
                                    x-on:click="rootExpanded = ! rootExpanded"
                                    x-text="rootExpanded ? 'Collapse' : 'Expand'"
                                ></button>
                            </div>
                        </div>
                        <div x-show="rootExpanded" x-transition x-cloak class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-lg bg-white/10 p-2"><strong class="text-gw-accent">{{ $root['metrics']['total_downline'] }}</strong><div>Team</div></div>
                            <div class="rounded-lg bg-white/10 p-2"><strong class="text-gw-accent">{{ $root['metrics']['direct_recruits'] }}</strong><div>Direct</div></div>
                            <div class="rounded-lg bg-white/10 p-2"><strong class="text-gw-accent">{{ $root['progress']['training'] }}%</strong><div>Training</div></div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto mt-5 h-8 w-px bg-gw-accent"></div>
                <div class="mx-auto h-px max-w-5xl bg-gw-accent"></div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    <template x-for="leader in filteredRows" :key="'org-leader-' + leader.id">
                        <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft p-5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <template x-if="leader.profile_photo_url">
                                    <img :src="leader.profile_photo_url" :alt="leader.name + ' photo'" class="h-11 w-11 shrink-0 rounded-full border border-gw-accent object-cover bg-gw-accent-muted">
                                </template>
                                <template x-if="! leader.profile_photo_url">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-gw-accent bg-gw-accent-muted text-sm font-bold text-gw-primary" x-text="leader.avatar"></span>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <a :href="leader.member_url" class="font-semibold text-gw-primary hover:text-gw-accent-dark" x-text="leader.name"></a>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-1 text-xs font-bold text-gw-primary" x-text="leader.rank"></span>
                                        <span x-text="leader.role_label"></span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col gap-1.5">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gw-primary bg-gw-primary px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-gw-accent hover:bg-gw-primary-hover"
                                        x-on:click="openProfile(leader)"
                                    >
                                        View Profile
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gw-accent bg-gw-accent-muted px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-gw-primary hover:bg-gw-accent-soft"
                                        x-on:click="toggleLeader(leader.id)"
                                        x-text="isLeaderExpanded(leader.id) ? 'Collapse' : 'Expand'"
                                    ></button>
                                </div>
                            </div>
                            <div x-show="isLeaderExpanded(leader.id)" x-transition x-cloak>
                                <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-lg border border-slate-300 bg-white p-3">
                                        <div class="font-bold text-gw-primary" x-text="leader.metrics.total_downline"></div>
                                        <div class="text-xs text-slate-500">Team Size</div>
                                    </div>
                                    <div class="rounded-lg border border-slate-300 bg-white p-3">
                                        <div class="font-bold text-gw-primary" x-text="leader.metrics.direct_recruits"></div>
                                        <div class="text-xs text-slate-500">Directs</div>
                                    </div>
                                    <div class="rounded-lg border border-slate-300 bg-white p-3">
                                        <div class="font-bold text-gw-primary" x-text="leader.active_associates"></div>
                                        <div class="text-xs text-slate-500">Active</div>
                                    </div>
                                    <div class="rounded-lg border border-slate-300 bg-white p-3">
                                        <div class="font-bold text-gw-primary" x-text="leader.licensed_associates"></div>
                                        <div class="text-xs text-slate-500">Licensed</div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <span>Branch Training</span>
                                        <span class="text-gw-primary" x-text="leader.progress.training + '%'"></span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-gw-accent" :style="'width:' + leader.progress.training + '%'"></div>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <a :href="leader.org_chart_url" class="rounded-lg border border-gw-accent px-3 py-2 text-xs font-bold text-gw-primary hover:bg-gw-accent-muted">Open Branch</a>
                                    <a :href="leader.tree_url" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Genealogy</a>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="totalCount === 0" class="rounded-lg border border-slate-300 bg-slate-50 p-6 text-center text-slate-600 lg:col-span-3">
                        No leader branches under this root yet.
                    </div>
                    <div x-show="totalCount > 0 && filteredCount === 0" x-cloak class="rounded-lg border border-slate-300 bg-slate-50 p-6 text-center text-slate-600 lg:col-span-3">
                        No leaders match your search or filters.
                    </div>
                </div>
            </div>

            @include('team.downline.partials.org-chart-profile-modal')
        </div>
    </section>
</x-app-layout>
