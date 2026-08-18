<x-app-layout>
    <section class="space-y-6">
        @include('resources.partials.hub-quick-nav')

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-8 text-white">
                <a href="{{ route('resources.index') }}" class="text-xs font-semibold uppercase tracking-wide text-gw-accent hover:text-white">← Resource Library</a>
                <p class="mt-3 text-sm font-semibold uppercase tracking-wide text-slate-300">Recorded Webinars</p>
                <h1 class="mt-1 text-2xl font-semibold">Webinar replays</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                    Team calls, field trainings, and replay links will be organized here for on-demand review.
                </p>
            </div>
            <div class="px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polygon points="10 8 16 12 10 16 10 8"></polygon>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gw-primary">Webinar library coming soon</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm text-slate-600">
                    Recorded webinar resources will appear here once they are published in Admin Management.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('resources.links') }}" class="inline-flex rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Browse links
                    </a>
                    <a href="{{ route('resources.index') }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent">
                        Back to library home
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
