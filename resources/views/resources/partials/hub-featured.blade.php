<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Featured resources</h2>
            <p class="mt-1 text-sm text-slate-600">High-priority documents and links to bookmark in your routine.</p>
        </div>
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start here</span>
    </div>

    <div class="grid gap-4 p-5 lg:grid-cols-2">
        @if ($featuredDocuments->isNotEmpty())
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Documents</h3>
                    <a href="{{ route('resources.documents') }}" class="text-xs font-semibold text-gw-accent-dark hover:underline">View all</a>
                </div>
                <div class="space-y-2">
                    @foreach ($featuredDocuments as $document)
                        @php
                            $categoryKey = $document->category ?: 'general';
                            $categoryMeta = $documentCategoryDefinitions[$categoryKey] ?? $documentCategoryDefinitions['general'];
                        @endphp
                        <a
                            href="{{ route('resources.documents', ['document' => $document->id]) }}"
                            class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3 transition hover:border-gw-accent/40 hover:bg-gw-accent-soft"
                        >
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gw-primary text-[10px] font-bold uppercase text-gw-accent">
                                {{ $document->resolvedFormat() }}
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gw-primary">{{ $document->title }}</span>
                                <span class="mt-1 block text-xs text-slate-500 line-clamp-2">{{ $document->description }}</span>
                                <span class="mt-2 inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $categoryMeta['accent'] }}">
                                    {{ $categoryMeta['label'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($featuredLinks->isNotEmpty())
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Links</h3>
                    <a href="{{ route('resources.links') }}" class="text-xs font-semibold text-gw-accent-dark hover:underline">View all</a>
                </div>
                <div class="space-y-2">
                    @foreach ($featuredLinks as $link)
                        <a
                            href="{{ $link->resolvedAccessUrl() }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50"
                        >
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-gw-primary">{{ $link->title }}</span>
                                <span class="mt-1 block text-xs text-slate-500 line-clamp-2">{{ $link->description }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
