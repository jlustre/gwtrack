<div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm text-amber-400">{{ $ticket->ticket_number }}</p>
            <h1 class="mt-1 text-xl font-semibold text-zinc-100">{{ $ticket->subject }}</h1>
            <p class="mt-2 text-sm text-zinc-400">{{ $ticket->description }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $ticket->status?->color_hex }}22; color: {{ $ticket->status?->color_hex }}">{{ $ticket->status?->name }}</span>
    </div>

    @if (session('support_status'))
        <div class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('support_status') }}</div>
    @endif

    @can('reopen', $ticket)
        <div class="mt-4">
            <button type="button" wire:click="reopen" class="rounded-lg border border-amber-500/40 px-4 py-2 text-sm font-semibold text-amber-300 hover:bg-amber-500/10">Reopen ticket</button>
        </div>
    @endcan

    <div class="mt-6 space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-400">Conversation</h2>
        @forelse ($ticket->comments as $comment)
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <p class="text-xs text-zinc-500">{{ $comment->user?->name }} · {{ $comment->created_at?->format('m/d/Y g:i A') }}</p>
                <p class="mt-2 text-sm text-zinc-200">{{ $comment->body }}</p>
            </div>
        @empty
            <p class="text-sm text-zinc-500">No replies yet.</p>
        @endforelse
    </div>

    @can('comment', $ticket)
        <form wire:submit="postComment" class="mt-6 space-y-3">
            <textarea wire:model="comment" rows="3" placeholder="Add a reply…" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 p-4 text-sm text-zinc-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/50"></textarea>
            @error('comment') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-black hover:bg-amber-600">Post reply</button>
        </form>
    @endcan
</div>
