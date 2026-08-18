<x-app-layout>
    <section class="space-y-6">
        @include('resources.partials.hub-hero')

        @include('resources.partials.hub-quick-nav')

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                @include('resources.partials.hub-library-cards')

                @if ($featuredDocuments->isNotEmpty() || $featuredLinks->isNotEmpty())
                    @include('resources.partials.hub-featured')
                @endif

                @if ($recentDocuments->isNotEmpty())
                    @include('resources.partials.hub-recent-documents')
                @endif
            </div>

            <aside class="space-y-5">
                @include('resources.partials.hub-favorites')

                @if ($documentCategories !== [] || $linkCategories !== [])
                    @include('resources.partials.hub-category-panels')
                @endif

                <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-gw-primary to-gw-primary-hover p-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Need something added?</p>
                    <p class="mt-2 text-sm leading-6 text-slate-200">
                        Documents and links are managed in Admin Management. Star favorites in the document library for quick access here.
                    </p>
                    @if ($canManageDocuments)
                        <a
                            href="{{ route('admin.management.resource.index', 'resources') }}"
                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                        >
                            Manage resources
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</x-app-layout>
