<div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
    <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Resource Library</p>
            <h1 class="mt-2 text-2xl font-semibold">Your field development toolkit</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                Documents, links, forms, and training assets to support onboarding, prospecting, compliance, and daily field work.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('resources.documents') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover"
            >
                Browse documents
            </a>
            <a
                href="{{ route('resources.links') }}"
                class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
            >
                Open links
            </a>
        </div>
    </div>

    <div class="grid gap-3 border-t border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] p-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Documents', 'value' => $stats['documents'], 'theme' => 'navy', 'subtitle' => 'Published files & guides', 'href' => route('resources.documents')],
            ['label' => 'Quick Links', 'value' => $stats['links'], 'theme' => 'cyan', 'subtitle' => 'Zoom, team & training URLs', 'href' => route('resources.links')],
            ['label' => 'My Favorites', 'value' => $stats['favorites'], 'theme' => 'gold', 'subtitle' => 'Starred for quick access', 'href' => route('resources.documents')],
            ['label' => 'Featured', 'value' => $stats['featured'], 'theme' => 'amber', 'subtitle' => 'Recommended starting points', 'href' => route('resources.documents')],
        ] as $card)
            <a href="{{ $card['href'] }}" class="block rounded-lg transition hover:scale-[1.01] focus:outline-none focus-visible:ring-2 focus-visible:ring-gw-accent">
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :theme="$card['theme']"
                />
            </a>
        @endforeach
    </div>
</div>
