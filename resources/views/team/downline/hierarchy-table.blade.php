<x-app-layout>
    <section
        class="space-y-6"
        x-data="downlineHierarchyTable(@js(['rows' => $rows, 'rootId' => $root->id, 'searchMembers' => $searchMembers]))"
    >
        <div class="rounded-lg border border-slate-700 bg-gradient-to-br from-[#05070B] via-[#07111F] to-[#1B2433] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Hierarchy Table</p>
                    <h1 class="mt-2 text-2xl font-semibold">Sponsor Hierarchy</h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-100">
                        @if ($isBranchView)
                            Branch rooted at
                            <span class="font-semibold text-gw-accent">{{ \App\Support\MemberDisplayName::for($root) }}</span>.
                            <span class="text-slate-300">&uarr;</span> on a member = make them topmost;
                            <span class="text-slate-300">&darr;</span> on the top row = step up to their direct upline (back to your hierarchy when you are the upline).
                        @else
                            Your downline from
                            <span class="font-semibold text-gw-accent">{{ \App\Support\MemberDisplayName::for($root) }}</span>.
                            <span class="text-slate-300">&uarr;</span> on a member = make them topmost;
                            <span class="text-slate-300">&darr;</span> on the top row = step up to their direct upline (back to your hierarchy when you are the upline).
                        @endif
                    </p>
                </div>
                <div class="flex w-full shrink-0 flex-col gap-1.5 sm:w-auto xl:w-[13.5rem]">
                    <div class="grid grid-cols-2 gap-1.5">
                        <button type="button" x-on:click="expandAll()" class="inline-flex items-center justify-center rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-center text-xs font-semibold text-white hover:bg-white/20 xl:text-sm">Expand All</button>
                        <button type="button" x-on:click="collapseAll()" class="inline-flex items-center justify-center rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-center text-xs font-semibold text-white hover:bg-white/20 xl:text-sm">Collapse All</button>
                        <a href="{{ route('team.tree') }}" class="inline-flex items-center justify-center rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-center text-xs font-semibold text-white hover:bg-white/20 xl:text-sm">Tree View</a>
                        <a href="{{ route('team.downlines') }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent px-2.5 py-1.5 text-center text-xs font-semibold text-gw-primary xl:text-sm">All Downlines</a>
                    </div>
                    @if ($isBranchView)
                        <a href="{{ route('team.hierarchy') }}" class="inline-flex items-center justify-center rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-center text-xs font-semibold text-white hover:bg-white/20 xl:text-sm">My Hierarchy</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-4 shadow-sm">
            <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Members by sponsorship level</h2>
                    <p class="text-sm text-slate-600">{{ count($rows) }} members in this branch</p>
                </div>
                <div
                    class="relative w-full max-w-md"
                    x-on:keydown.escape.window="closeMemberSearch()"
                    x-on:click.outside="closeMemberSearch()"
                >
                    <label class="sr-only" for="hierarchy-member-search">Search members in this hierarchy</label>
                    <input
                        id="hierarchy-member-search"
                        type="search"
                        autocomplete="off"
                        x-model="memberSearch"
                        x-on:input="onMemberSearchInput()"
                        x-on:focus="onMemberSearchInput()"
                        x-on:keydown.arrow-down.prevent="highlightNextMatch()"
                        x-on:keydown.arrow-up.prevent="highlightPreviousMatch()"
                        x-on:keydown.enter.prevent="selectHighlightedMember()"
                        placeholder="Search your full hierarchy (min. 3 characters)..."
                        class="h-10 w-full rounded-lg border-slate-300 text-sm"
                    >
                    <ul
                        x-show="memberSearchOpen && memberSearchMatches().length > 0"
                        x-cloak
                        class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 shadow-lg"
                        role="listbox"
                    >
                        <template x-for="(match, index) in memberSearchMatches()" :key="'hierarchy-search-' + match.id">
                            <li role="option">
                                <button
                                    type="button"
                                    class="flex w-full items-center px-3 py-2 text-left text-sm text-gw-primary hover:bg-gw-accent-muted"
                                    :class="index === memberSearchHighlight ? 'bg-gw-accent-muted' : ''"
                                    x-on:click="selectMember(match)"
                                    x-text="match.name"
                                ></button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="memberSearch.trim().length > 0 && memberSearch.trim().length < 3" class="mt-1 text-xs text-slate-500">
                        Type at least 3 characters to search this hierarchy.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white/90">
                <table class="min-w-full text-sm leading-tight">
                    <thead class="sticky top-0 bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-2 py-1.5">Member</th>
                            <th class="w-16 px-2 py-1.5 text-right">Team</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80">
                        <template x-for="row in rows" :key="'hierarchy-row-' + row.id">
                            <tr
                                x-show="isVisible(row)"
                                class="hover:bg-gw-accent-soft/60"
                                :class="row.id === rootId ? 'bg-gw-accent-muted/50' : ''"
                            >
                                <td class="px-2 py-0.5">
                                    <div
                                        class="flex min-h-[1.625rem] items-center gap-1"
                                        :style="`padding-left: ${row.depth * 0.875}rem`"
                                    >
                                        <span class="inline-flex w-5 shrink-0 items-center justify-center">
                                            <button
                                                type="button"
                                                x-show="row.has_children"
                                                x-on:click="toggle(row.id)"
                                                class="inline-flex h-5 w-5 items-center justify-center rounded border border-gw-accent/60 bg-white text-xs font-bold text-gw-primary hover:bg-gw-accent-muted"
                                                :aria-label="isExpanded(row.id) ? 'Collapse branch' : 'Expand branch'"
                                            >
                                                <span x-show="! isExpanded(row.id)">+</span>
                                                <span x-show="isExpanded(row.id)">−</span>
                                            </button>
                                            <span x-show="! row.has_children" class="text-xs text-slate-300">·</span>
                                        </span>
                                        <span class="min-w-0 truncate text-sm font-semibold text-gw-primary" x-text="row.name"></span>
                                        <div class="flex w-[4.125rem] shrink-0 items-center justify-end gap-0.5">
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                                <a
                                                    x-show="row.make_top_url"
                                                    :href="row.make_top_url"
                                                    title="Make this member the topmost"
                                                    class="gw-icon-btn-dark-accent"
                                                >
                                                    <span class="sr-only">Make this member the topmost</span>
                                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 3.25a.75.75 0 0 1 .53.22l4.25 4.25a.75.75 0 0 1-1.06 1.06L10.75 5.81V16a.75.75 0 0 1-1.5 0V5.81L6.28 8.78a.75.75 0 0 1-1.06-1.06l4.25-4.25a.75.75 0 0 1 .53-.22Z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            </span>
                                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                                <a
                                                    x-show="row.upline_hierarchy_url"
                                                    :href="row.upline_hierarchy_url"
                                                    title="Make direct upline the topmost"
                                                    class="gw-icon-btn-primary"
                                                >
                                                    <span class="sr-only">Make direct upline the topmost</span>
                                                    <svg class="h-3 w-3 rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 3.25a.75.75 0 0 1 .53.22l4.25 4.25a.75.75 0 0 1-1.06 1.06L10.75 5.81V16a.75.75 0 0 1-1.5 0V5.81L6.28 8.78a.75.75 0 0 1-1.06-1.06l4.25-4.25a.75.75 0 0 1 .53-.22Z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            </span>
                                            <button
                                                type="button"
                                                x-on:click="openProfile(row)"
                                                class="gw-icon-btn-xs"
                                                title="View member summary"
                                            >
                                                <span class="sr-only">View summary</span>
                                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0ZM9.555 7.168A1 1 0 0 0 8 8v4a1 1 0 0 0 1.555.832l3-2a1 1 0 0 0 0-1.664l-3-2Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-0.5 text-right tabular-nums text-sm text-slate-600" x-text="row.total_downline"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <p x-show="rows.length === 0" class="mt-4 text-center text-sm text-slate-500">No members in this branch.</p>
        </div>

        @include('team.downline.partials.hierarchy-profile-modal')
    </section>
</x-app-layout>
