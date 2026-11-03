<x-app-layout>
    <section class="space-y-6" x-data="genealogyTreePan(@js(['searchUrl' => route('team.tree.search')]))">
        <div class="rounded-lg border border-slate-700 bg-gradient-to-br from-[#05070B] via-[#07111F] to-[#1B2433] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Genealogy View</p>
                    <h1 class="mt-2 text-2xl font-semibold">Sponsor Tree</h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-100">Closure-table powered hierarchy with expandable branches, rank badges, progress, and member actions. Drag horizontally on the tree canvas to pan left and right.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" x-on:click="compact = ! compact" class="rounded-lg border border-gw-accent bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary">Compact</button>
                    <a href="{{ route('team.hierarchy') }}" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">Hierarchy Table</a>
                    <a href="{{ route('team.downlines') }}" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">All Downlines</a>
                </div>
            </div>
        </div>

        <div class="grid gap-3 rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-4 shadow-sm lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))]">
            <div
                class="relative"
                x-on:keydown.escape.window="closeMemberSearch()"
                x-on:click.outside="closeMemberSearch()"
            >
                <label class="sr-only" for="genealogy-member-search">Search members in your hierarchy</label>
                <input
                    id="genealogy-member-search"
                    type="search"
                    autocomplete="off"
                    x-model="memberSearch"
                    x-on:input="onMemberSearchInput()"
                    x-on:focus="onMemberSearchInput()"
                    x-on:keydown.arrow-down.prevent="highlightNextMatch()"
                    x-on:keydown.arrow-up.prevent="highlightPreviousMatch()"
                    x-on:keydown.enter.prevent="selectHighlightedMember()"
                    placeholder="Search your hierarchy (min. 3 characters)..."
                    class="h-10 w-full rounded-lg border-slate-300 text-sm"
                >
                <ul
                    x-show="memberSearchOpen && memberSearchMatches().length > 0"
                    x-cloak
                    class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 shadow-lg"
                    role="listbox"
                >
                    <template x-for="(match, index) in memberSearchMatches()" :key="'genealogy-search-' + match.id">
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
                    Type at least 3 characters to search your hierarchy.
                </p>
                <p x-show="memberSearchLoading" x-cloak class="mt-1 text-xs text-slate-500">
                    Searching…
                </p>
                <p x-show="! memberSearchLoading && memberSearch.trim().length >= 3 && memberSearchMatches().length === 0" x-cloak class="mt-1 text-xs text-slate-500">
                    No members match your search.
                </p>
            </div>

            <form method="GET" class="contents">
                <select name="rank_id" class="h-10 rounded-lg border-slate-300 text-sm">
                    <option value="">All Ranks</option>
                    @foreach ($filters['ranks'] as $rank)
                        <option value="{{ $rank->id }}" @selected((string) request('rank_id') === (string) $rank->id)>{{ $rank->code }} - {{ $rank->name }}</option>
                    @endforeach
                </select>
                <select name="country" class="h-10 rounded-lg border-slate-300 text-sm">
                    <option value="">All Countries</option>
                    @foreach ($filters['countries'] as $country)
                        <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                    @endforeach
                </select>
                <button class="h-10 rounded-lg bg-gw-primary px-4 text-sm font-semibold text-white">Apply Filters</button>
            </form>
        </div>

        <div class="relative min-h-[calc(100vh-21rem)] overflow-hidden rounded-lg border border-slate-400 bg-[#05070B] shadow-sm">
            <div class="absolute left-3 top-3 z-20 flex flex-col gap-2">
                <button
                    type="button"
                    x-on:click="zoomIn()"
                    title="Zoom in"
                    class="gw-icon-btn-accent text-lg font-bold leading-none"
                >
                    <span class="sr-only">Zoom in</span>
                    <span aria-hidden="true">+</span>
                </button>
                <button
                    type="button"
                    x-on:click="zoomOut()"
                    title="Zoom out"
                    class="gw-icon-btn-accent text-lg font-bold leading-none"
                >
                    <span class="sr-only">Zoom out</span>
                    <span aria-hidden="true">−</span>
                </button>
            </div>

            <div
                x-ref="surface"
                class="h-full min-h-[calc(100vh-21rem)] cursor-grab overflow-x-auto overflow-y-auto"
                x-on:mousedown="startPan($event)"
                x-on:mousemove.window="pan($event)"
                x-on:mouseup.window="endPan()"
                x-on:mouseleave.window="endPan()"
                x-on:touchstart.passive="startPanTouch($event)"
                x-on:touchmove="pan($event)"
                x-on:touchend.window="endPan()"
                x-on:touchcancel.window="endPan()"
            >
            <div
                class="inline-block min-w-full px-12 pb-6 pt-14 transition"
                :style="`transform: scale(${zoom}); transform-origin: top left;`"
            >
                <div class="mx-auto flex w-max flex-col items-center">
                    @include('team.downline.partials.tree-node', ['node' => $root, 'isRoot' => true])

                    @if ($children->isNotEmpty())
                        <div class="h-8 w-px shrink-0 bg-gw-accent {{ $children->count() > 1 ? '-mb-px' : '' }}" aria-hidden="true"></div>

                        <div class="relative inline-flex items-start justify-center gap-5">
                            @if ($children->count() > 1)
                                <div
                                    class="pointer-events-none absolute top-0 h-px bg-gw-accent"
                                    style="left: 7rem; width: calc(100% - 14rem);"
                                    aria-hidden="true"
                                ></div>
                            @endif

                            @foreach ($children as $child)
                                @include('team.downline.partials.tree-branch-node', ['node' => $child])
                            @endforeach
                        </div>
                    @else
                        <p class="mt-8 rounded-lg border border-white/10 bg-white/5 px-6 py-4 text-center text-slate-300">No direct recruits under this root yet.</p>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </section>
</x-app-layout>
