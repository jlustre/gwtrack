@php
    $categoryKey = $link->category ?: 'general';
    $categoryMeta = $categories[$categoryKey] ?? $categories['general'];
    $linkUrl = $link->resolvedAccessUrl();
    $linkHost = $linkUrl ? parse_url($linkUrl, PHP_URL_HOST) : null;
@endphp

<article
    class="flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-gw-accent/40 hover:shadow-md"
    x-data="{ copied: false, copyLink() { if (! @js($linkUrl)) return; navigator.clipboard.writeText(@js($linkUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }"
>
    <div class="border-b border-slate-100 bg-gradient-to-r from-[#F8FAFC] to-white px-5 py-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $categoryMeta['accent'] }}">
                        {{ $categoryMeta['label'] }}
                    </span>
                    @if (($featured ?? false) || $link->is_featured)
                        <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-gw-accent-dark">
                            Featured
                        </span>
                    @endif
                </div>
                @if ($linkUrl)
                    <a
                        href="{{ $linkUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 block text-left text-base font-semibold text-gw-primary transition hover:text-gw-accent"
                    >
                        {{ $link->title }}
                    </a>
                @else
                    <h3 class="mt-2 text-base font-semibold text-gw-primary">{{ $link->title }}</h3>
                @endif
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-sky-100 bg-sky-50 text-sky-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </svg>
            </div>
        </div>
    </div>

    <div class="flex flex-1 flex-col px-5 py-4">
        <p class="line-clamp-3 text-sm leading-6 text-slate-600">
            {{ $link->description ?: 'No description provided.' }}
        </p>

        @if ($linkHost)
            <p class="mt-3 truncate text-xs font-medium text-slate-500">{{ $linkHost }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="text-xs text-slate-500">Updated {{ $link->updated_at?->format('M j, Y') }}</span>
            @if ($linkUrl)
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="copyLink()"
                        class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft"
                    >
                        <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                    <a
                        href="{{ $linkUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#13345f]"
                    >
                        Open link
                    </a>
                </div>
            @endif
        </div>
    </div>
</article>
