<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('communications.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">← Back to Communication Hub</a>
        <button
            type="button"
            wire:click="toggleBookmark"
            @class([
                'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition',
                'border-gw-accent bg-gw-accent-soft text-gw-accent-dark' => $isBookmarked,
                'border-slate-300 bg-white text-slate-700 hover:border-gw-accent' => ! $isBookmarked,
            ])
        >
            {{ $isBookmarked ? 'Saved' : 'Save for later' }}
        </button>
    </div>

    @if (session('communication_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('communication_status') }}
        </div>
    @endif

    @php
        $priorityMeta = $priorities[$announcement->priority] ?? ['label' => ucfirst($announcement->priority), 'color' => '#64748B'];
    @endphp

    <article class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-white shadow-sm">
        @if ($announcement->hero_image_path)
            <div class="aspect-[21/9] w-full bg-slate-100">
                <img src="{{ Storage::url($announcement->hero_image_path) }}" alt="" class="h-full w-full object-cover">
            </div>
        @endif

        <div class="border-b border-gw-primary/10 bg-gradient-to-r from-gw-primary to-[#132a4d] px-6 py-8 text-white">
            <div class="flex flex-wrap items-center gap-2">
                @if ($announcement->category)
                    <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-gw-accent">
                        {{ $announcement->category->name }}
                    </span>
                @endif
                @if ($recognitionContext['honoree'] ?? null)
                    <span class="rounded-full bg-gw-accent/20 px-2.5 py-0.5 text-xs font-semibold text-gw-accent">
                        Honoree: {{ $recognitionContext['honoree']['name'] }}
                    </span>
                @endif
                @if ($recognitionContext['badge'] ?? null)
                    <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-white">
                        {{ $recognitionContext['badge']['icon'] }} {{ $recognitionContext['badge']['name'] }}
                    </span>
                @endif
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style="background-color: {{ $priorityMeta['color'] }}">
                    {{ $priorityMeta['label'] }}
                </span>
            </div>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">{{ $announcement->title }}</h1>
            @if ($announcement->summary)
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-200">{{ $announcement->summary }}</p>
            @endif
            <p class="mt-4 text-xs text-slate-300">
                {{ $announcement->creator?->name ?? 'System' }}
                · Published {{ $announcement->published_at?->format('M j, Y g:i A') }}
                @if ($announcement->view_count > 0)
                    · {{ number_format($announcement->view_count) }} {{ Str::plural('view', $announcement->view_count) }}
                @endif
            </p>
        </div>

        <div class="prose prose-slate max-w-none px-6 py-8">
            {!! nl2br(e($announcement->body)) !!}
        </div>

        @if ($announcement->campaign)
            <div class="border-t border-gw-accent/30 bg-gw-accent-soft px-6 py-4">
                <p class="text-sm text-slate-700">This announcement is part of the <strong>{{ $announcement->campaign->name }}</strong> campaign.</p>
                <a href="{{ route('communications.campaigns.show', $announcement->campaign) }}" class="mt-2 inline-flex text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">View campaign leaderboard →</a>
            </div>
        @endif

        @if ($announcement->calendar_event_id && $announcement->category?->code === 'event')
            <livewire:communication.announcement-event-rsvp :announcement="$announcement" :key="'event-rsvp-'.$announcement->id" />
        @endif

        @if ($announcement->requires_acknowledgement)
            <div class="border-t border-slate-200 bg-gw-accent-soft px-6 py-5">
                @if ($hasAcknowledged)
                    <p class="text-sm font-semibold text-emerald-700">You acknowledged this announcement.</p>
                @else
                    <p class="text-sm text-slate-700">This announcement requires your acknowledgement.</p>
                    <button
                        type="button"
                        wire:click="acknowledge"
                        class="mt-3 rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]"
                    >
                        I acknowledge
                    </button>
                @endif
            </div>
        @endif

        <div class="border-t border-slate-200 px-6 py-5">
            <livewire:communication.announcement-reactions :announcement="$announcement" :key="'reactions-'.$announcement->id" />
        </div>

        <livewire:communication.announcement-comments :announcement="$announcement" :key="'comments-'.$announcement->id" />
    </article>
</div>
