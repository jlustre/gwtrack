<x-app-layout>
    @php
        $categories = $library['categories'];
        $categoryCounts = $library['categoryCounts'];
        $filters = $library['filters'];
        $stats = $library['stats'];
        $featured = $library['featured'];
        $videos = $library['videos'];
        $activeCategory = $filters['category'];
        $chipClass = fn (bool $active = false) => 'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition '
            .($active
                ? 'border-gw-primary bg-gw-primary text-white'
                : 'border-slate-200 bg-white text-slate-700 hover:border-gw-accent hover:bg-gw-accent-soft');
    @endphp

    <section
        class="space-y-6"
        x-data="{
            showPlayer: false,
            playerLoading: false,
            playerError: null,
            playerData: null,
            init() {
                const previewId = @js($previewVideoId);
                if (previewId) {
                    this.openPlayer(previewId);
                }
            },
            async openPlayer(videoId) {
                this.showPlayer = true;
                this.playerLoading = true;
                this.playerError = null;
                this.playerData = null;

                const url = new URL(window.location.href);
                url.searchParams.set('video', videoId);
                window.history.replaceState({}, '', url);

                try {
                    const response = await fetch(@js(url('/resources/videos')) + '/' + videoId + '/preview', {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('Failed to load video');
                    }

                    this.playerData = await response.json();
                } catch (error) {
                    this.playerError = 'Could not load this video. Try opening the external link instead.';
                } finally {
                    this.playerLoading = false;
                }
            },
            closePlayer() {
                this.showPlayer = false;
                this.playerLoading = false;
                this.playerError = null;
                this.playerData = null;

                const url = new URL(window.location.href);
                url.searchParams.delete('video');
                window.history.replaceState({}, '', url);
            },
        }"
    >
        @include('resources.partials.hub-quick-nav')

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-violet-50/40 shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <a href="{{ route('resources.index') }}" class="text-xs font-semibold uppercase tracking-wide text-gw-accent hover:text-white">← Resource Library</a>
                        <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-300">Videos</p>
                        <h1 class="mt-1 text-2xl font-semibold">Video library</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                            Short training clips, leadership messages, product education, and recruiting presentations — ready when you need a quick refresher.
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
                    <a href="{{ route('resources.videos', request()->except('category', 'page', 'video')) }}" class="{{ $chipClass(! $activeCategory) }}">
                        All videos
                        <span class="ml-1.5 rounded-full bg-black/10 px-1.5 py-0.5 text-[0.65rem]">{{ $stats['total'] }}</span>
                    </a>
                    @foreach ($categories as $key => $category)
                        @php($count = (int) ($categoryCounts[$key] ?? 0))
                        @if ($count > 0)
                            <a href="{{ route('resources.videos', array_merge(request()->except('page', 'video'), ['category' => $key])) }}" class="{{ $chipClass($activeCategory === $key) }}">
                                {{ $category['label'] }}
                                <span class="ml-1.5 rounded-full bg-black/10 px-1.5 py-0.5 text-[0.65rem]">{{ $count }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                <form method="GET" action="{{ route('resources.videos') }}" class="flex gap-2">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Search videos..."
                        class="h-10 min-w-56 rounded-lg border-slate-200 bg-white px-3 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                    <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Search</button>
                </form>
            </div>

            @if ($featured->isNotEmpty() && ! $filters['search'] && ! $activeCategory)
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Featured videos</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($featured as $video)
                            @include('resources.partials.video-card', ['video' => $video, 'favoriteResourceIds' => $favoriteResourceIds, 'featured' => true])
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="px-6 py-5">
                @if ($videos->count() === 0)
                    <div class="py-10 text-center">
                        <h2 class="text-lg font-semibold text-gw-primary">No videos found</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            @if ($filters['search'] || $activeCategory)
                                Try clearing filters or browse another category.
                            @else
                                Published video resources will appear here once they are added in Admin Management.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($videos as $video)
                            @include('resources.partials.video-card', ['video' => $video, 'favoriteResourceIds' => $favoriteResourceIds])
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $videos->links() }}</div>
                @endif
            </div>
        </div>

        <div
            x-show="showPlayer"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            x-on:keydown.escape.window="closePlayer()"
        >
            <div class="w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl" x-on:click.outside="closePlayer()">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Now playing</p>
                        <h2 class="mt-1 text-lg font-semibold text-gw-primary" x-text="playerData?.title ?? 'Video'"></h2>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" x-on:click="closePlayer()">Close</button>
                </div>
                <div class="aspect-video bg-black">
                    <template x-if="playerLoading">
                        <div class="flex h-full items-center justify-center text-sm text-white">Loading video...</div>
                    </template>
                    <template x-if="playerError">
                        <div class="flex h-full items-center justify-center px-6 text-center text-sm text-white" x-text="playerError"></div>
                    </template>
                    <template x-if="playerData && playerData.provider === 'file'">
                        <video class="h-full w-full" controls :src="playerData.embed_url"></video>
                    </template>
                    <template x-if="playerData && playerData.provider !== 'file' && playerData.embed_url">
                        <iframe class="h-full w-full" :src="playerData.embed_url" title="Video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    </template>
                </div>
                <div class="px-5 py-4 text-sm text-slate-600" x-show="playerData?.description" x-text="playerData?.description"></div>
            </div>
        </div>
    </section>
</x-app-layout>
