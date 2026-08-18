<div class="border-t border-slate-200 px-6 py-6">
    <div class="mb-4 flex items-center justify-between gap-2">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">
            Discussion
            @if ($commentCount > 0)
                <span class="text-slate-500">({{ $commentCount }})</span>
            @endif
        </h2>
    </div>

    @if (! $replyToId)
        <form wire:submit="postComment" class="mb-6 space-y-3">
            <label for="comment-body" class="sr-only">Add a comment</label>
            <textarea
                id="comment-body"
                wire:model="body"
                rows="3"
                placeholder="Share your thoughts..."
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            ></textarea>
            @error('body')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <button
                type="submit"
                class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#132a4d]"
            >
                Post comment
            </button>
        </form>
    @endif

    <div class="space-y-4">
        @forelse ($comments as $comment)
            <div wire:key="comment-{{ $comment->id }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gw-primary">{{ $comment->user?->name ?? 'User' }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-700">{{ $comment->body }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $comment->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" wire:click="startReply({{ $comment->id }})" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary">
                            Reply
                        </button>
                        @if ($comment->user_id === auth()->id() || auth()->user()->can('delete announcements'))
                            <button type="button" wire:click="deleteComment({{ $comment->id }})" class="text-xs font-semibold text-red-600 hover:text-red-700">
                                Delete
                            </button>
                        @endif
                    </div>
                </div>

                @if ($replyToId === $comment->id)
                    <form wire:submit="postComment" class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        <textarea
                            wire:model="body"
                            rows="2"
                            placeholder="Write a reply..."
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        @error('body')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-gw-accent">Reply</button>
                            <button type="button" wire:click="cancelReply" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600">Cancel</button>
                        </div>
                    </form>
                @endif

                @if ($comment->replies->isNotEmpty())
                    <div class="mt-4 space-y-3 border-l-2 border-gw-accent/30 pl-4">
                        @foreach ($comment->replies as $reply)
                            <div wire:key="reply-{{ $reply->id }}" class="rounded-lg bg-white p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gw-primary">{{ $reply->user?->name ?? 'User' }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-700">{{ $reply->body }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $reply->created_at?->diffForHumans() }}</p>
                                    </div>
                                    @if ($reply->user_id === auth()->id() || auth()->user()->can('delete announcements'))
                                        <button type="button" wire:click="deleteComment({{ $reply->id }})" class="text-xs font-semibold text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                No comments yet. Start the discussion.
            </p>
        @endforelse
    </div>
</div>
