<x-app-layout>
    @php
        $categories = $library['categories'];
        $categoryCounts = $library['categoryCounts'];
        $filters = $library['filters'];
        $stats = $library['stats'];
        $featured = $library['featured'];
        $documents = $library['documents'];
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
            showCategorySidebar: true,
            showPreview: false,
            previewLoading: false,
            previewError: null,
            previewData: null,
            previewView: 'rtf',
            init() {
                const saved = localStorage.getItem('gw-documents-view');
                this.viewMode = saved === 'cards' ? 'cards' : 'table';

                const savedSidebar = localStorage.getItem('gw-documents-category-sidebar');
                this.showCategorySidebar = savedSidebar !== 'hidden';
            },
            setView(mode) {
                this.viewMode = mode;
                localStorage.setItem('gw-documents-view', mode);
            },
            toggleCategorySidebar() {
                this.showCategorySidebar = ! this.showCategorySidebar;
                localStorage.setItem(
                    'gw-documents-category-sidebar',
                    this.showCategorySidebar ? 'visible' : 'hidden',
                );
            },
            async openPreview(documentId) {
                this.showPreview = true;
                this.previewLoading = true;
                this.previewError = null;
                this.previewData = null;
                this.previewView = 'rtf';

                const url = new URL(window.location.href);
                url.searchParams.set('document', documentId);
                window.history.replaceState({}, '', url);

                try {
                    const response = await fetch(@js(url('/resources/documents')) + '/' + documentId + '/preview', {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('Failed to load document preview');
                    }

                    this.previewData = await response.json();
                    this.previewView = this.previewData.default_view || 'rtf';
                } catch (error) {
                    this.previewError = 'Could not load this document preview. Please try again.';
                } finally {
                    this.previewLoading = false;
                }
            },
            closePreview() {
                this.showPreview = false;
                this.previewLoading = false;
                this.previewError = null;
                this.previewData = null;
                this.previewView = 'rtf';

                const url = new URL(window.location.href);
                url.searchParams.delete('document');
                window.history.replaceState({}, '', url);
            },
        }"
        x-init="@if ($previewDocumentId) $nextTick(() => openPreview(@js($previewDocumentId))) @endif"
    >
        @include('resources.partials.hub-quick-nav')

        @if (session('status') === 'document-seeder-updated')
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <p class="font-semibold">Document seeder updated</p>
                <p class="mt-1">
                    Exported {{ (int) session('seeder_count', 0) }} document{{ (int) session('seeder_count', 0) === 1 ? '' : 's' }}
                    to <code class="rounded bg-white/70 px-1 py-0.5 text-xs">database/seeders/ResourceDocumentSeeder.php</code>.
                    Run <code class="rounded bg-white/70 px-1 py-0.5 text-xs">php artisan migrate:fresh --seed</code> to restore them.
                    Regenerate stored PDFs from Admin Management after seeding if needed.
                </p>
            </div>
        @endif

        @if (session('status') === 'favorite-added')
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                Document added to My Favorites.
            </div>
        @endif

        @if (session('status') === 'favorite-removed')
            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                Document removed from My Favorites.
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <a href="{{ route('resources.index') }}" class="text-xs font-semibold uppercase tracking-wide text-gw-accent hover:text-white">← Resource Library</a>
                        <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-300">Documents</p>
                        <h1 class="mt-1 text-2xl font-semibold">Document library</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                            Onboarding packets, forms, scripts, guides, and compliance resources to support your field development.
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
                    <a href="{{ route('resources.documents', request()->except('category', 'page')) }}" class="{{ $chipClass(! $activeCategory) }}">
                        All documents
                        <span class="ml-1.5 rounded-full bg-black/10 px-1.5 py-0.5 text-[0.65rem]">{{ $stats['total'] }}</span>
                    </a>
                    @foreach ($categories as $key => $category)
                        @php($count = (int) ($categoryCounts[$key] ?? 0))
                        @if ($count > 0)
                            <a
                                href="{{ route('resources.documents', array_merge(request()->except('page'), ['category' => $key])) }}"
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

                    @if ($canUpdateDocumentSeeder)
                        <form method="POST" action="{{ route('resources.documents.update-seeder') }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft"
                            >
                                Update document seeder
                            </button>
                        </form>
                    @endif

                    @if ($canManageDocuments)
                        <a
                            href="{{ route('admin.management.create', 'resources') }}"
                            class="inline-flex items-center justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                        >
                            New document
                        </a>
                        <a
                            href="{{ route('admin.management.resource.index', 'resources') }}"
                            class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
                        >
                            Manage documents
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <form method="GET" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto]">
            @if ($activeCategory)
                <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif
            <label class="sr-only" for="document-search">Search documents</label>
            <input
                id="document-search"
                name="search"
                value="{{ $filters['search'] }}"
                placeholder="Search by title or description"
                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
            <button class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#13345f]">
                Search
            </button>
        </form>

        <div class="overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm">
            <div class="border-b border-gw-accent/20 bg-gw-accent/5 px-4 py-3">
                <h2 class="text-sm font-semibold text-gw-primary">My Favorites</h2>
                <p class="mt-1 text-xs text-slate-600">Documents you have starred for quick access.</p>
            </div>
            <div x-show="viewMode === 'table'" x-cloak>
                @include('resources.partials.document-table', [
                    'documents' => $favoriteRecords,
                    'categories' => $categories,
                    'favoriteResourceIds' => $favoriteResourceIds,
                    'filters' => $filters,
                    'emptyMessage' => 'No favorites yet. Star a document in the list below to add it here.',
                ])
            </div>
            <div x-show="viewMode === 'cards'" x-cloak class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($favoriteRecords as $document)
                    @include('resources.partials.document-card', [
                        'document' => $document,
                        'categories' => $categories,
                        'featured' => false,
                        'favoriteResourceIds' => $favoriteResourceIds,
                        'filters' => $filters,
                    ])
                @empty
                    <p class="col-span-full px-2 py-6 text-center text-sm text-slate-500">
                        No favorites yet. Star a document in the list below to add it here.
                    </p>
                @endforelse
            </div>
        </div>

        @if ($featured->isNotEmpty() && ! $activeCategory && ! filled($filters['search']))
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gw-primary">Featured documents</h2>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start here</span>
                </div>
                <div x-show="viewMode === 'table'" x-cloak>
                    @include('resources.partials.document-table', [
                        'documents' => $featured,
                        'categories' => $categories,
                        'showFeaturedBadge' => true,
                        'favoriteResourceIds' => $favoriteResourceIds,
                        'filters' => $filters,
                    ])
                </div>

                <div x-show="viewMode === 'cards'" x-cloak class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($featured as $document)
                        @include('resources.partials.document-card', [
                            'document' => $document,
                            'categories' => $categories,
                            'featured' => true,
                            'favoriteResourceIds' => $favoriteResourceIds,
                            'filters' => $filters,
                        ])
                    @endforeach
                </div>
            </div>
        @endif

        <div
            class="grid gap-6"
            :class="showCategorySidebar ? 'xl:grid-cols-[16rem_minmax(0,1fr)]' : 'xl:grid-cols-1'"
        >
            <aside class="hidden xl:block" x-show="showCategorySidebar" x-cloak>
                <div class="sticky top-24 space-y-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-semibold text-gw-primary">Browse by category</h2>
                    <nav class="space-y-2">
                        <a href="{{ route('resources.documents', request()->except('category', 'page')) }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ ! $activeCategory ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                            All documents
                        </a>
                        @foreach ($categories as $key => $category)
                            @php($count = (int) ($categoryCounts[$key] ?? 0))
                            @if ($count > 0)
                                <a
                                    href="{{ route('resources.documents', array_merge(request()->except('page'), ['category' => $key])) }}"
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
                                {{ $categories[$activeCategory]['label'] ?? 'Documents' }}
                            @else
                                All documents
                            @endif
                        </h2>
                        @if ($activeCategory)
                            <p class="mt-1 text-sm text-slate-600">{{ $categories[$activeCategory]['description'] ?? '' }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="toggleCategorySidebar()"
                            class="hidden items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft xl:inline-flex"
                            :aria-expanded="showCategorySidebar"
                            :title="showCategorySidebar ? 'Hide category panel' : 'Show category panel'"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 6h16M4 12h10M4 18h16" />
                            </svg>
                            <span x-text="showCategorySidebar ? 'Hide categories' : 'Show categories'"></span>
                        </button>
                        @include('resources.partials.library-view-toggle')
                        <span class="text-sm font-medium text-slate-500">{{ $documents->total() }} result{{ $documents->total() === 1 ? '' : 's' }}</span>
                    </div>
                </div>

                @if ($documents->count() > 0)
                    <div x-show="viewMode === 'table'" x-cloak>
                        @include('resources.partials.document-table', [
                            'documents' => $documents,
                            'categories' => $categories,
                            'favoriteResourceIds' => $favoriteResourceIds,
                            'filters' => $filters,
                        ])
                    </div>

                    <div x-show="viewMode === 'cards'" x-cloak class="grid gap-4 md:grid-cols-2">
                        @foreach ($documents as $document)
                            @include('resources.partials.document-card', [
                                'document' => $document,
                                'categories' => $categories,
                                'featured' => false,
                                'favoriteResourceIds' => $favoriteResourceIds,
                                'filters' => $filters,
                            ])
                        @endforeach
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        {{ $documents->links() }}
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-gw-primary">No documents found</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            @if (filled($filters['search']) || $activeCategory)
                                Try clearing your filters or searching with a different keyword.
                            @else
                                Published documents will appear here once they are added in Admin Management.
                            @endif
                        </p>
                        @if (filled($filters['search']) || $activeCategory)
                            <a href="{{ route('resources.documents') }}" class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                                Clear filters
                            </a>
                        @elseif ($canManageDocuments)
                            <a href="{{ route('admin.management.create', 'resources') }}" class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                                New document
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @include('resources.partials.document-preview-modal')
    </section>
</x-app-layout>
