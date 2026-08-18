@php
    $quickLinks = $home['quick_links'] ?? [];
    $links = $quickLinks['links'] ?? collect();
    $categories = $quickLinks['categories'] ?? [];
    $libraryUrl = $quickLinks['library_url'] ?? null;

    // ROYGBIV gradients (inline so they always render without a Tailwind rebuild)
    $rainbowGradients = [
        ['background' => 'linear-gradient(135deg, #ef4444 0%, #dc2626 45%, #be123c 100%)', 'darkText' => false], // Red
        ['background' => 'linear-gradient(135deg, #fb923c 0%, #f97316 45%, #c2410c 100%)', 'darkText' => false], // Orange
        ['background' => 'linear-gradient(135deg, #fde047 0%, #facc15 45%, #ca8a04 100%)', 'darkText' => true],  // Yellow
        ['background' => 'linear-gradient(135deg, #4ade80 0%, #22c55e 45%, #15803d 100%)', 'darkText' => false], // Green
        ['background' => 'linear-gradient(135deg, #38bdf8 0%, #3b82f6 45%, #1d4ed8 100%)', 'darkText' => false], // Blue
        ['background' => 'linear-gradient(135deg, #818cf8 0%, #6366f1 45%, #3730a3 100%)', 'darkText' => false], // Indigo
        ['background' => 'linear-gradient(135deg, #c084fc 0%, #a855f7 45%, #7e22ce 100%)', 'darkText' => false], // Violet
    ];
@endphp

@if ($links->isNotEmpty())
    <section>
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Links</h2>
                <p class="mt-1 text-sm text-slate-500">Zoom rooms, team calls, training sessions, and other quick-access URLs.</p>
            </div>

            @if ($libraryUrl)
                <a
                    href="{{ $libraryUrl }}"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-gw-primary transition hover:text-gw-accent"
                >
                    View all links
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            @endif
        </div>

        <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($links as $link)
                @php
                    $categoryKey = $link->category ?: 'general';
                    $categoryMeta = $categories[$categoryKey] ?? $categories['general'] ?? ['label' => 'General', 'accent' => 'bg-slate-100 text-slate-700 border-slate-200'];
                    $linkUrl = $link->resolvedAccessUrl();
                    $linkHost = $linkUrl ? parse_url($linkUrl, PHP_URL_HOST) : null;
                    $theme = $rainbowGradients[$loop->index % count($rainbowGradients)];
                    $darkText = $theme['darkText'];
                    $titleClass = $darkText ? 'text-gw-primary' : 'text-white';
                    $bodyClass = $darkText ? 'text-gw-primary/80' : 'text-white/90';
                    $iconWrapClass = $darkText ? 'bg-gw-primary/10 text-gw-primary' : 'bg-white/20 text-white';
                    $badgeClass = $darkText
                        ? 'border-gw-primary/20 bg-white/55 text-gw-primary'
                        : 'border-white/35 bg-white/20 text-white';
                    $featuredClass = $darkText
                        ? 'bg-gw-primary/10 text-gw-primary'
                        : 'bg-white/25 text-white';
                @endphp

                @if ($linkUrl)
                    <a
                        href="{{ $linkUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex w-full items-start gap-3 rounded-lg border border-black/5 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        style="background: {{ $theme['background'] }};"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconWrapClass }} transition group-hover:scale-105">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>

                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-1.5">
                                <span class="block text-sm font-semibold {{ $titleClass }}">{{ $link->title }}</span>
                                @if ($link->is_featured)
                                    <span class="rounded-full px-1.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide {{ $featuredClass }}">Featured</span>
                                @endif
                            </span>

                            <span class="mt-0.5 block text-xs {{ $bodyClass }}">
                                @if (filled($link->description))
                                    {{ str($link->description)->limit(72) }}
                                @elseif ($linkHost)
                                    {{ $linkHost }}
                                @else
                                    {{ $categoryMeta['label'] }}
                                @endif
                            </span>

                            <span class="mt-1.5 inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $badgeClass }}">
                                {{ $categoryMeta['label'] }}
                            </span>
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </section>
@endif
