<div class="grid gap-4 px-6 py-5 transition hover:bg-white/65 lg:grid-cols-[auto_1fr_auto] lg:items-center">
    <div class="flex h-11 w-11 items-center justify-center rounded-full {{ $task['tone'] }} text-white shadow-sm">
        @if ($task['type'] === 'Confirmation')
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M9 11l3 3L22 4" />
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
        @elseif ($task['type'] === 'CFM Assignment')
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M19 8v6" />
                <path d="M22 11h-6" />
            </svg>
        @elseif ($task['type'] === 'Email Follow-Up')
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect width="20" height="16" x="2" y="4" rx="2" />
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
            </svg>
        @else
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 2v20" />
                <path d="m17 5-5-3-5 3" />
                <path d="m17 19-5 3-5-3" />
                <path d="M4 12h16" />
            </svg>
        @endif
    </div>

    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="font-semibold text-gw-primary">{{ $task['title'] }}</h3>
            <span class="rounded-full {{ $task['priority'] === 'High' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700' }} px-2 py-0.5 text-xs font-bold">{{ $task['priority'] }}</span>
            <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-xs font-bold text-gw-accent-dark">{{ $task['type'] }}</span>
        </div>
        <p class="mt-1 text-sm font-medium text-slate-700">{{ $task['subtitle'] }}</p>
        @if ($task['description'])
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $task['description'] }}</p>
        @endif
        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">{{ $task['meta'] }}</span>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">{{ $task['age'] }}</span>
            @if ($task['member_email'])
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">{{ $task['member_email'] }}</span>
            @endif
        </div>
    </div>

    <div class="space-y-3 lg:min-w-72">
        @if ($task['type'] === 'Confirmation' && isset($task['review_url']))
            <form method="POST" action="{{ $task['review_url'] }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <label for="task_review_comments_{{ $task['id'] }}" class="sr-only">Confirmation comments</label>
                <textarea
                    id="task_review_comments_{{ $task['id'] }}"
                    name="review_comments"
                    rows="2"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    placeholder="Add confirmation notes..."
                ></textarea>
                <div class="grid grid-cols-2 gap-2">
                    <button name="decision" value="rejected" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">Reject</button>
                    <button name="decision" value="confirmed" class="rounded-md bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Confirm</button>
                </div>
            </form>
            <a href="{{ $task['action_url'] }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-gw-accent hover:text-gw-primary">
                Open source page
            </a>
        @else
            <div class="flex items-center gap-2 lg:justify-end">
                <a href="{{ $task['action_url'] }}" class="inline-flex items-center justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                    {{ $task['action_label'] }}
                </a>
            </div>
        @endif
    </div>
</div>
