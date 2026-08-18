<x-app-layout>

    @php

        $categories = $library['categories'];

        $categoryCounts = $library['categoryCounts'];

        $filters = $library['filters'];

        $stats = $library['stats'];

        $featured = $library['featured'];

        $links = $library['links'];

        $activeCategory = $filters['category'];

        $chipClass = fn (bool $active = false) => 'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition '

            .($active

                ? 'border-gw-primary bg-gw-primary text-white'

                : 'border-slate-200 bg-white text-slate-700 hover:border-gw-accent hover:bg-gw-accent-soft');

    @endphp



    <section

        class="space-y-6"

        x-data="{

            viewMode: 'table',

            init() {

                const saved = localStorage.getItem('gw-links-view');

                this.viewMode = saved === 'cards' ? 'cards' : 'table';

            },

            setView(mode) {

                this.viewMode = mode;

                localStorage.setItem('gw-links-view', mode);

            },

        }"

    >

        @include('resources.partials.hub-quick-nav')

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">

            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

                    <div>

                        <a href="{{ route('resources.index') }}" class="text-xs font-semibold uppercase tracking-wide text-gw-accent hover:text-white">← Resource Library</a>

                        <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-300">Links</p>

                        <h1 class="mt-1 text-2xl font-semibold">Quick links</h1>

                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">

                            Zoom rooms, team calls, training sessions, mentor meetings, and other quick-access links for your daily field work.

                        </p>

                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">

                        <div class="rounded-md bg-white/10 px-4 py-3">

                            <div class="text-xs uppercase tracking-wide text-slate-300">Published</div>

                            <div class="mt-1 text-xl font-semibold">{{ $stats['total'] }}</div>

                        </div>

                        <div class="rounded-md bg-white/10 px-4 py-3">

                            <div class="text-xs uppercase tracking-wide text-slate-300">Featured</div>

                            <div class="mt-1 text-xl font-semibold">{{ $stats['featured'] }}</div>

                        </div>

                        <div class="rounded-md bg-white/10 px-4 py-3">

                            <div class="text-xs uppercase tracking-wide text-slate-300">Categories</div>

                            <div class="mt-1 text-xl font-semibold">{{ $stats['categories'] }}</div>

                        </div>

                    </div>

                </div>

            </div>



            <div class="flex flex-col gap-3 border-b border-slate-100 bg-white/80 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">

                <div class="flex flex-wrap gap-2">

                    <a href="{{ route('resources.links', request()->except('category', 'page')) }}" class="{{ $chipClass(! $activeCategory) }}">

                        All links

                        <span class="ml-1.5 rounded-full bg-black/10 px-1.5 py-0.5 text-[0.65rem]">{{ $stats['total'] }}</span>

                    </a>

                    @foreach ($categories as $key => $category)

                        @php($count = (int) ($categoryCounts[$key] ?? 0))

                        @if ($count > 0)

                            <a

                                href="{{ route('resources.links', array_merge(request()->except('page'), ['category' => $key])) }}"

                                class="{{ $chipClass($activeCategory === $key) }}"

                            >

                                {{ $category['label'] }}

                                <span class="ml-1.5 rounded-full bg-black/10 px-1.5 py-0.5 text-[0.65rem]">{{ $count }}</span>

                            </a>

                        @endif

                    @endforeach

                </div>



                <div class="flex flex-wrap items-center gap-3">

                    @include('resources.partials.library-view-toggle')



                    @if ($canManageResources)

                        <a

                            href="{{ route('admin.management.resource.index', 'resources') }}"

                            class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-muted"

                        >

                            Manage links

                        </a>

                    @endif

                </div>

            </div>

        </div>



        <form method="GET" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto]">

            @if ($activeCategory)

                <input type="hidden" name="category" value="{{ $activeCategory }}">

            @endif

            <label class="sr-only" for="link-search">Search links</label>

            <input

                id="link-search"

                name="search"

                value="{{ $filters['search'] }}"

                placeholder="Search by title, description, or URL"

                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"

            >

            <button class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#13345f]">

                Search

            </button>

        </form>



        @if ($featured->isNotEmpty() && ! $activeCategory && ! filled($filters['search']))

            <div class="space-y-4">

                <div class="flex items-center justify-between gap-3">

                    <h2 class="text-lg font-semibold text-gw-primary">Featured links</h2>

                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pin to your routine</span>

                </div>



                <div x-show="viewMode === 'table'" x-cloak>

                    @include('resources.partials.link-table', [

                        'links' => $featured,

                        'categories' => $categories,

                        'showFeaturedBadge' => true,

                    ])

                </div>



                <div x-show="viewMode === 'cards'" x-cloak class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                    @foreach ($featured as $link)

                        @include('resources.partials.link-card', ['link' => $link, 'categories' => $categories, 'featured' => true])

                    @endforeach

                </div>

            </div>

        @endif



        <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)]">

            <aside class="hidden xl:block">

                <div class="sticky top-24 space-y-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">

                    <h2 class="text-sm font-semibold text-gw-primary">Browse by category</h2>

                    <nav class="space-y-2">

                        <a href="{{ route('resources.links', request()->except('category', 'page')) }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ ! $activeCategory ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-50' }}">

                            All links

                        </a>

                        @foreach ($categories as $key => $category)

                            @php($count = (int) ($categoryCounts[$key] ?? 0))

                            @if ($count > 0)

                                <a

                                    href="{{ route('resources.links', array_merge(request()->except('page'), ['category' => $key])) }}"

                                    class="block rounded-md px-3 py-2 text-sm {{ $activeCategory === $key ? 'bg-gw-accent-soft font-semibold text-gw-primary' : 'text-slate-600 hover:bg-slate-50' }}"

                                >

                                    <span class="font-medium">{{ $category['label'] }}</span>

                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $category['description'] }}</span>

                                </a>

                            @endif

                        @endforeach

                    </nav>

                </div>

            </aside>



            <div class="space-y-4">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h2 class="text-lg font-semibold text-gw-primary">

                            @if ($activeCategory)

                                {{ $categories[$activeCategory]['label'] ?? 'Links' }}

                            @else

                                All links

                            @endif

                        </h2>

                        @if ($activeCategory)

                            <p class="mt-1 text-sm text-slate-600">{{ $categories[$activeCategory]['description'] ?? '' }}</p>

                        @endif

                    </div>

                    <div class="flex items-center gap-3">

                        @include('resources.partials.library-view-toggle')

                        <span class="text-sm font-medium text-slate-500">{{ $links->total() }} result{{ $links->total() === 1 ? '' : 's' }}</span>

                    </div>

                </div>



                @if ($links->count() > 0)

                    <div x-show="viewMode === 'table'" x-cloak>

                        @include('resources.partials.link-table', [

                            'links' => $links,

                            'categories' => $categories,

                        ])

                    </div>



                    <div x-show="viewMode === 'cards'" x-cloak class="grid gap-4 md:grid-cols-2">

                        @foreach ($links as $link)

                            @include('resources.partials.link-card', ['link' => $link, 'categories' => $categories, 'featured' => false])

                        @endforeach

                    </div>



                    <div class="border-t border-slate-200 pt-4">

                        {{ $links->links() }}

                    </div>

                @else

                    <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">

                        <h3 class="text-lg font-semibold text-gw-primary">No links found</h3>

                        <p class="mt-2 text-sm text-slate-600">

                            @if (filled($filters['search']) || $activeCategory)

                                Try clearing your filters or searching with a different keyword.

                            @else

                                Published links will appear here once they are added in Admin Management.

                            @endif

                        </p>

                        @if (filled($filters['search']) || $activeCategory)

                            <a href="{{ route('resources.links') }}" class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">

                                Clear filters

                            </a>

                        @endif

                    </div>

                @endif

            </div>

        </div>

    </section>

</x-app-layout>

