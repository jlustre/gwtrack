<div class="space-y-6">
    <div>
        <a href="{{ route('communications.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Back to Communication Hub</a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-gw-accent">Saved announcements</p>
        <h1 class="text-2xl font-semibold text-gw-primary">Your bookmarks</h1>
        <p class="mt-2 text-sm text-slate-600">Announcements you saved to read or reference later.</p>
    </div>

    <div class="rounded-2xl border border-gw-primary/10 bg-white p-4 shadow-sm">
        <label for="bookmark-search" class="sr-only">Search saved announcements</label>
        <input
            id="bookmark-search"
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search saved announcements..."
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
        >
    </div>

    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            @php
                $priorityMeta = $priorities[$announcement->priority] ?? ['label' => ucfirst($announcement->priority), 'color' => '#64748B'];
                $isUnread = ! $readIds->contains($announcement->id);
            @endphp
            <article class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($announcement->category)
                                <span class="rounded-full bg-gw-primary px-2.5 py-0.5 text-xs font-semibold text-gw-accent">
                                    {{ $announcement->category->name }}
                                </span>
                            @endif
                            @if ($isUnread)
                                <span class="rounded-full bg-gw-accent-soft px-2.5 py-0.5 text-xs font-semibold text-gw-accent-dark">Unread</span>
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
                    </div>
                    <div class="flex flex-col gap-2">
                        <a
                            href="{{ route('communications.show', $announcement) }}"
                            class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-3 py-2 text-center text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20"
                        >
                            Open
                        </a>
                        <button
                            type="button"
                            wire:click="removeBookmark({{ $announcement->id }})"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-red-300 hover:text-red-600"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gw-primary">No saved announcements</p>
                <p class="mt-2 text-sm text-slate-500">Bookmark announcements from the feed or detail page to find them here.</p>
            </div>
        @endforelse
    </div>

    {{ $announcements->links() }}
</div>
