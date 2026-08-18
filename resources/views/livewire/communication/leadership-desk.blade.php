<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('communications.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Communication Hub</a>
            <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-gw-accent">Leadership Desk</p>
            <h1 class="text-2xl font-semibold text-gw-primary">Messages from leadership</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Weekly leadership messages, vision updates, culture posts, and strategic initiatives from agency leadership.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('communications.recognition') }}" class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft">
                Recognition Center
            </a>
            @if ($canCreate)
                <a href="{{ route('communications.create') }}" class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]">
                    New leadership message
                </a>
            @endif
        </div>
    </div>

    @if ($featured)
        <section class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-white shadow-lg">
            <div class="bg-gradient-to-r from-gw-primary via-[#132a4d] to-gw-primary px-6 py-8 text-white md:px-10 md:py-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gw-accent">Featured leadership message</p>
                <h2 class="mt-4 max-w-4xl text-2xl font-semibold leading-tight md:text-3xl">{{ $featured->title }}</h2>
                @if ($featured->summary)
                    <p class="mt-4 max-w-3xl text-base leading-7 text-slate-200">{{ $featured->summary }}</p>
                @endif
                <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-slate-300">
                    <span>{{ $featured->creator?->name ?? 'Leadership' }}</span>
                    <span>·</span>
                    <span>{{ $featured->published_at?->format('M j, Y') }}</span>
                </div>
                <a href="{{ route('communications.show', $featured) }}" class="mt-6 inline-flex rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                    Read full message
                </a>
            </div>
        </section>
    @endif

    <div class="rounded-2xl border border-gw-primary/10 bg-white p-4 shadow-sm">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search leadership messages..."
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
        >
    </div>

    <div class="space-y-4">
        @forelse ($messages as $message)
            <article class="rounded-2xl border border-gw-primary/10 bg-white p-6 shadow-sm transition hover:border-gw-accent/40">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Leadership Message</p>
                        <h2 class="mt-2 text-xl font-semibold text-gw-primary">
                            <a href="{{ route('communications.show', $message) }}" class="hover:text-gw-accent-dark">{{ $message->title }}</a>
                        </h2>
                        @if ($message->summary)
                            <blockquote class="mt-4 border-l-4 border-gw-accent pl-4 text-base italic leading-7 text-slate-700">
                                {{ $message->summary }}
                            </blockquote>
                        @endif
                        <p class="mt-4 text-xs text-slate-500">
                            {{ $message->creator?->name ?? 'Leadership' }}
                            · {{ $message->published_at?->diffForHumans() }}
                        </p>
                        <p class="mt-2 text-sm font-semibold text-gw-accent-dark">{{ config('communication-hub.leadership_desk.signature_prefix') }}</p>
                    </div>
                    <a href="{{ route('communications.show', $message) }}" class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-3 py-2 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                        Read more
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gw-primary">No leadership messages yet</p>
            </div>
        @endforelse
    </div>

    {{ $messages->links() }}
</div>
