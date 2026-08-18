<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Communication Hub</p>
            <h1 class="text-2xl font-semibold text-gw-primary">Announcements & updates</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Company news, leadership messages, training updates, recognition, and compliance notices from your organization.
            </p>
            @if ($unreadCount > 0)
                <p class="mt-2 text-sm font-semibold text-gw-accent-dark">{{ $unreadCount }} unread {{ Str::plural('announcement', $unreadCount) }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('communications.leadership') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Leadership Desk
            </a>
            <a
                href="{{ route('communications.recognition') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Recognition
            </a>
            <a
                href="{{ route('communications.campaigns.index') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Campaigns
            </a>
            <a
                href="{{ route('communications.archive') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Archive
            </a>
            <a
                href="{{ route('communications.bookmarks') }}"
                class="inline-flex items-center rounded-lg border border-gw-accent/50 bg-white px-4 py-2 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent-soft"
            >
                Saved
            </a>
        @if ($canCreate)
            <a
                href="{{ route('communications.create') }}"
                class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]"
            >
                New announcement
            </a>
        @endif
        @can('view communication analytics')
            <a
                href="{{ route('admin.communications.index') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Analytics
            </a>
        @endcan
        @can('send broadcast')
            <a
                href="{{ route('admin.communications.broadcasts') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Broadcast
            </a>
        @endcan
        @can('view communication analytics')
            <a
                href="{{ route('admin.communications.acknowledgements') }}"
                class="inline-flex items-center rounded-lg border border-gw-primary/15 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
            >
                Ack report
            </a>
        @endcan
        </div>
    </div>

    @if (session('communication_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('communication_status') }}
        </div>
    @endif

    @if ($featured->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-gradient-to-br from-gw-primary via-[#132a4d] to-gw-primary shadow-lg">
            <div class="border-b border-white/10 px-5 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gw-accent">Featured</p>
            </div>
            <div class="grid gap-0 md:grid-cols-{{ min($featured->count(), 3) }}">
                @foreach ($featured as $item)
                    @php
                        $priorityMeta = $priorities[$item->priority] ?? ['label' => ucfirst($item->priority), 'color' => '#64748B'];
                        $isUnread = ! $readIds->contains($item->id);
                    @endphp
                    <a
                        href="{{ route('communications.show', $item) }}"
                        @class([
                            'group block border-white/10 p-5 transition hover:bg-white/5 md:border-r last:md:border-r-0',
                            'ring-2 ring-inset ring-gw-accent/40' => $isUnread,
                        ])
                    >
                        @if ($item->category)
                            <span class="text-xs font-semibold uppercase tracking-wide text-gw-accent">{{ $item->category->name }}</span>
                        @endif
                        <h2 class="mt-2 text-lg font-semibold text-white group-hover:text-gw-accent">{{ $item->title }}</h2>
                        @if ($item->summary)
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-300">{{ $item->summary }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                            <span class="rounded-full px-2 py-0.5 font-semibold text-white" style="background-color: {{ $priorityMeta['color'] }}">{{ $priorityMeta['label'] }}</span>
                            <span>{{ $item->published_at?->diffForHumans() }}</span>
                            @if ($isUnread)
                                <span class="font-semibold text-gw-accent">Unread</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($pinned->isNotEmpty())
        <section class="rounded-2xl border border-gw-accent/30 bg-gw-accent-soft p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Pinned updates</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($pinned as $item)
                    <a
                        href="{{ route('communications.show', $item) }}"
                        class="inline-flex items-center gap-2 rounded-full border border-gw-accent/40 bg-white px-3 py-1.5 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft"
                    >
                        @if (! $readIds->contains($item->id))
                            <span class="h-2 w-2 rounded-full bg-gw-accent" aria-hidden="true"></span>
                        @endif
                        {{ $item->title }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="rounded-2xl border border-gw-primary/10 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="communication-search" class="sr-only">Search announcements</label>
                <input
                    id="communication-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search headlines and content..."
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
            </div>
            <select wire:model.live="categoryId" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="priority" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All priorities</option>
                @foreach ($priorities as $code => $meta)
                    <option value="{{ $code }}">{{ $meta['label'] }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="unreadOnly" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                Unread only
            </label>
        </div>
        @if ($hasActiveFilters)
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            @php
                $priorityMeta = $priorities[$announcement->priority] ?? ['label' => ucfirst($announcement->priority), 'color' => '#64748B'];
                $isUnread = ! $readIds->contains($announcement->id);
                $isBookmarked = $bookmarkIds->contains($announcement->id);
            @endphp
            <article @class([
                'overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:border-gw-accent/40',
                'border-l-4 border-l-gw-accent' => $announcement->is_pinned,
                'border-gw-primary/10' => ! $announcement->is_pinned,
                'ring-1 ring-gw-accent/20' => $isUnread,
            ])>
                @if ($announcement->hero_image_path)
                    <div class="aspect-[21/9] w-full bg-slate-100">
                        <img src="{{ Storage::url($announcement->hero_image_path) }}" alt="" class="h-full w-full object-cover">
                    </div>
                @endif
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($announcement->category)
                                    <span class="rounded-full bg-gw-primary px-2.5 py-0.5 text-xs font-semibold text-gw-accent">
                                        {{ $announcement->category->name }}
                                    </span>
                                @endif
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style="background-color: {{ $priorityMeta['color'] }}">
                                    {{ $priorityMeta['label'] }}
                                </span>
                                @if ($isUnread)
                                    <span class="rounded-full bg-gw-accent-soft px-2.5 py-0.5 text-xs font-semibold text-gw-accent-dark">Unread</span>
                                @endif
                                @if ($announcement->is_pinned)
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Pinned</span>
                                @endif
                                @if ($announcement->requires_acknowledgement)
                                    <span class="text-xs font-semibold uppercase tracking-wide text-red-600">Ack required</span>
                                @endif
                                @if ($isBookmarked)
                                    <span class="text-xs font-semibold text-gw-accent-dark">Saved</span>
                                @endif
                            </div>
                            <h2 class="mt-3 text-lg font-semibold text-gw-primary">
                                <a href="{{ route('communications.show', $announcement) }}" class="hover:text-gw-accent-dark">
                                    {{ $announcement->title }}
                                </a>
                            </h2>
                            @if ($announcement->summary)
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $announcement->summary }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span>{{ $announcement->creator?->name ?? 'System' }}</span>
                                <span>·</span>
                                <span>{{ $announcement->published_at?->diffForHumans() }}</span>
                                @if ($announcement->view_count > 0)
                                    <span>·</span>
                                    <span>{{ number_format($announcement->view_count) }} {{ Str::plural('view', $announcement->view_count) }}</span>
                                @endif
                                @php($engagement = $engagementSummaries[$announcement->id] ?? ['reactions' => 0, 'comments' => 0])
                                @if ($engagement['reactions'] > 0)
                                    <span>·</span>
                                    <span>{{ $engagement['reactions'] }} {{ Str::plural('reaction', $engagement['reactions']) }}</span>
                                @endif
                                @if ($engagement['comments'] > 0)
                                    <span>·</span>
                                    <span>{{ $engagement['comments'] }} {{ Str::plural('comment', $engagement['comments']) }}</span>
                                @endif
                            </div>
                        </div>
                        <a
                            href="{{ route('communications.show', $announcement) }}"
                            class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-3 py-2 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20"
                        >
                            Read more
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gw-primary">
                    @if ($hasActiveFilters)
                        No announcements match your filters
                    @else
                        No announcements yet
                    @endif
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    @if ($hasActiveFilters)
                        Try adjusting search or filters to see more results.
                    @else
                        Published updates for your audience will appear here.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{ $announcements->links() }}
</div>
