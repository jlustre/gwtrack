@php
    $categoryKey = $document->category ?: 'general';
    $categoryMeta = $categories[$categoryKey] ?? $categories['general'];
    $format = $document->resolvedFormat();
    $actionUrl = $document->resolvedAccessUrl();
    $formUrl = $document->formUrl();
    $favoriteResourceIds = $favoriteResourceIds ?? [];
    $filters = $filters ?? [];
    $isFavorited = in_array($document->id, $favoriteResourceIds, true);
@endphp



<article class="flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-gw-accent/40 hover:shadow-md">

    <div class="border-b border-slate-100 bg-gradient-to-r from-[#F8FAFC] to-white px-5 py-4">

        <div class="flex items-start justify-between gap-3">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2">

                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $categoryMeta['accent'] }}">

                        {{ $categoryMeta['label'] }}

                    </span>

                    @if (($featured ?? false) || $document->is_featured)

                        <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-gw-accent-dark">

                            Featured

                        </span>

                    @endif

                </div>

                @if ($document->isInteractiveForm() && $formUrl)

                    <a

                        href="{{ $formUrl }}"

                        class="mt-2 block text-left text-base font-semibold text-gw-primary transition hover:text-gw-accent"

                    >

                        {{ $document->title }}

                    </a>

                @else

                    <button

                        type="button"

                        @click="openPreview({{ $document->id }})"

                        class="mt-2 text-left text-base font-semibold text-gw-primary transition hover:text-gw-accent"

                    >

                        {{ $document->title }}

                    </button>

                @endif

            </div>

            <div class="flex shrink-0 flex-col items-end gap-2">
                @unless ($document->isInteractiveForm())
                    @include('resources.partials.portal-resource-favorite-button', [
                        'formAction' => route('resources.documents.favorite', $document),
                        'queryParams' => [
                            'search' => $filters['search'] ?? '',
                            'category' => $filters['category'] ?? '',
                        ],
                        'isFavorited' => $isFavorited,
                    ])
                @endunless
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-xs font-bold text-gw-primary">
                    {{ $format }}
                </div>
            </div>

        </div>

    </div>



    <div class="flex flex-1 flex-col px-5 py-4">

        <p class="line-clamp-3 text-sm leading-6 text-slate-600">

            {{ $document->description ?: 'No description provided.' }}

        </p>



        <div class="mt-auto flex items-center justify-between gap-3 pt-4">

            <span class="text-xs text-slate-500">Updated {{ $document->updated_at?->format('M j, Y') }}</span>

            <div class="flex items-center gap-2">

                @if ($document->isInteractiveForm() && $formUrl)

                    <a

                        href="{{ $formUrl }}"

                        class="inline-flex items-center gap-1 rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#13345f]"

                    >

                        Fill Form

                    </a>

                @else

                    <button

                        type="button"

                        @click="openPreview({{ $document->id }})"

                        class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft"

                    >

                        View

                    </button>

                    @if ($document->shouldOfferListDownload())

                        <a

                            href="{{ $actionUrl }}"

                            @if (! $document->hasDownloadableFile()) target="_blank" rel="noopener noreferrer" @endif

                            class="inline-flex items-center gap-1 rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#13345f]"

                        >

                            {{ $document->hasDownloadableFile() ? 'Download' : 'Open' }}

                        </a>

                    @endif

                @endif

            </div>

        </div>

    </div>

</article>


