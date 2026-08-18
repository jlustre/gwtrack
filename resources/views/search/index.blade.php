<x-app-layout>
    @php
        $sections = $results['sections'] ?? [];
        $total = (int) ($results['total'] ?? 0);
        $minLength = (int) config('global-search.min_query_length', 2);
    @endphp

    <section class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Portal Search</p>
                <h1 class="mt-2 text-2xl font-semibold">Search GWTrack</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    Find team members, prospects, resources, videos, training, tasks, events, and announcements from one place.
                </p>
            </div>

            <form method="GET" action="{{ route('search.index') }}" class="border-b border-slate-100 bg-white/80 px-6 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="relative flex-1">
                        <label for="search-query" class="sr-only">Search query</label>
                        <input
                            id="search-query"
                            name="q"
                            type="search"
                            value="{{ $query }}"
                            placeholder="Search members, training, resources, prospects..."
                            class="h-11 w-full rounded-lg border-slate-200 bg-slate-50 pl-4 pr-4 text-sm shadow-sm focus:border-gw-accent focus:bg-white focus:ring-gw-accent"
                        >
                    </div>
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif
                    <button type="submit" class="rounded-lg bg-gw-accent px-5 py-2.5 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Search
                    </button>
                </div>
            </form>

            @if ($query !== '' && mb_strlen($query) < $minLength)
                <div class="px-6 py-8 text-sm text-slate-600">
                    Enter at least {{ $minLength }} characters to search.
                </div>
            @elseif ($query === '')
                <div class="px-6 py-8">
                    <p class="text-sm text-slate-600">Start typing in the search box above, or use the top bar search from any page.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (config('global-search.sections', []) as $key => $section)
                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $section['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            @elseif ($total === 0)
                <div class="px-6 py-10 text-center">
                    <h2 class="text-lg font-semibold text-gw-primary">No results for "{{ $query }}"</h2>
                    <p class="mt-2 text-sm text-slate-600">Try a different keyword, check spelling, or browse the resource library and team tools directly.</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('resources.index') }}" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Resource library</a>
                        <a href="{{ route('team.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary">Team command center</a>
                    </div>
                </div>
            @else
                <div class="border-b border-slate-100 bg-slate-50 px-6 py-3 text-sm text-slate-600">
                    {{ $total }} {{ str('result')->plural($total) }} for <span class="font-semibold text-gw-primary">"{{ $query }}"</span>
                </div>

                @if (! $activeType)
                    <div class="flex flex-wrap gap-2 border-b border-slate-100 px-6 py-3">
                        <a href="{{ route('search.index', ['q' => $query]) }}" class="rounded-full border border-gw-primary bg-gw-primary px-3 py-1 text-xs font-semibold text-white">All</a>
                        @foreach ($sections as $section)
                            <a
                                href="{{ route('search.index', ['q' => $query, 'type' => $section['key']]) }}"
                                class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:border-gw-accent"
                            >
                                {{ $section['label'] }} ({{ $section['count'] }})
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-6 px-6 py-6">
                    @foreach ($sections as $section)
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h2 class="text-base font-semibold text-gw-primary">{{ $section['label'] }}</h2>
                                <p class="mt-1 text-xs text-slate-500">{{ $section['count'] }} {{ str('match')->plural($section['count']) }}</p>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @foreach ($section['results'] as $result)
                                    <li>
                                        <a href="{{ $result['url'] }}" class="flex flex-col gap-1 px-5 py-4 transition hover:bg-gw-accent-soft sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="font-semibold text-gw-primary">{{ $result['title'] }}</p>
                                                <p class="mt-1 text-sm text-slate-600">{{ $result['subtitle'] }}</p>
                                            </div>
                                            @if (! empty($result['meta']))
                                                <p class="text-xs text-slate-500 sm:max-w-xs sm:text-right">{{ $result['meta'] }}</p>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
