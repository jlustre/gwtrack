<main
    class="flex min-h-[36rem] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    x-data="{
        scrollToLatest(smooth = true) {
            this.$nextTick(() => {
                const thread = this.$refs.thread;

                if (! thread) {
                    return;
                }

                thread.scrollTo({
                    top: thread.scrollHeight,
                    behavior: smooth ? 'smooth' : 'auto',
                });
            });
        },
    }"
    x-init="scrollToLatest(false)"
    @scroll-to-latest-message.window="scrollToLatest(true)"
>
    <div class="flex items-center justify-between border-b border-slate-200 bg-[#F8FAFC] px-4 py-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">{{ $selectedConversation->displayNameFor(auth()->user()) }}</h2>
            <p class="text-xs text-slate-500">{{ ucfirst($selectedConversation->type) }} conversation</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="$set('showTemplates', true)" class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-white">Templates</button>
            <button type="button" wire:click="archiveSelected" wire:confirm="Archive this conversation?" class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-white">Archive</button>
        </div>
    </div>

    <div
        x-ref="thread"
        class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4"
    >
        @foreach ($messages as $message)
            @php($isMine = (int) $message->user_id === (int) auth()->id())
            <div wire:key="message-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-2.5 shadow-sm {{ $isMine ? 'rounded-br-md bg-gw-primary text-white' : 'rounded-bl-md border border-slate-200 bg-white text-gw-primary' }}">
                    @unless ($isMine)
                        <p class="mb-1 text-[0.65rem] font-bold uppercase tracking-wide text-gw-accent">{{ $message->sender?->name }}</p>
                    @endunless
                    <p class="whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[0.65rem] {{ $isMine ? 'text-slate-300' : 'text-slate-400' }}">
                        <span>{{ $message->created_at->format('g:i A') }}</span>
                        @if ($message->edited_at)
                            <span>Edited</span>
                        @endif
                        @if ($isMine && $message->reads->isNotEmpty())
                            <span>Read</span>
                        @endif
                    </div>
                    @if ($message->reactions->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($message->reactions->groupBy('reaction') as $reaction => $items)
                                <button
                                    type="button"
                                    wire:click="reactToMessage({{ $message->id }}, @js($reaction))"
                                    title="Toggle reaction"
                                    class="rounded-full bg-black/10 px-2 py-0.5 text-xs transition hover:bg-black/20"
                                >
                                    {{ $reaction }} {{ $items->count() }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-2 flex flex-wrap items-center gap-1">
                        @unless ($isMessagingSuspended)
                        <div
                            x-data="{ open: false }"
                            class="relative inline-flex"
                            @click.outside="open = false"
                        >
                            <button
                                type="button"
                                @click="open = ! open"
                                class="rounded px-1.5 py-0.5 text-sm hover:bg-black/10"
                                title="Add reaction"
                                aria-label="Add reaction"
                                aria-haspopup="true"
                                :aria-expanded="open"
                            >
                                😊
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute bottom-full z-30 mb-1 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg {{ $isMine ? 'right-0' : 'left-0' }}"
                            >
                                <p class="mb-2 px-1 text-[0.65rem] font-bold uppercase tracking-wide text-slate-500">React</p>
                                <div class="grid max-h-40 grid-cols-8 gap-0.5 overflow-y-auto">
                                    @foreach ($reactions as $reaction)
                                        <button
                                            type="button"
                                            wire:click="reactToMessage({{ $message->id }}, @js($reaction))"
                                            @click="open = false"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-lg transition hover:bg-gw-accent-soft"
                                            title="React with {{ $reaction }}"
                                        >
                                            {{ $reaction }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endunless
                        @unless ($isMessagingSuspended)
                        <button type="button" wire:click="setReply({{ $message->id }})" wire:loading.attr="disabled" wire:target="setReply" class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold uppercase hover:bg-black/10">Reply</button>
                        <button type="button" wire:click="openTaskModal({{ $message->id }})" class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold uppercase hover:bg-black/10">Task</button>
                        <button type="button" wire:click="deleteMessageForMe({{ $message->id }})" class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold uppercase hover:bg-black/10">Delete</button>
                        @if ($isMine && $message->created_at->gt(now()->subMinutes($deleteWindowMinutes)))
                            <button type="button" wire:click="deleteMessageForEveryone({{ $message->id }})" class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold uppercase hover:bg-black/10">Delete for all</button>
                        @endif
                        @endunless
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="border-t border-slate-200 bg-white p-4">
        @if ($isMessagingSuspended)
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Messaging suspended</p>
                <p class="mt-1">You can read this conversation but cannot send messages.</p>
                @if ($messagingSuspensionReason)
                    <p class="mt-2"><span class="font-semibold">Reason:</span> {{ $messagingSuspensionReason }}</p>
                @endif
            </div>
        @else
        @if ($replyToMessageId)
            <div class="mb-2 flex items-center justify-between rounded-md bg-gw-accent-soft px-3 py-2 text-xs text-gw-accent-dark">
                <span>Replying to message #{{ $replyToMessageId }}</span>
                <button type="button" wire:click="cancelReply" class="font-semibold">Cancel</button>
            </div>
        @endif
        <form wire:submit.prevent="sendMessage" data-no-page-loader class="flex gap-2">
            <textarea
                wire:model.live.debounce.150ms="messageBody"
                rows="2"
                placeholder="Write a message... (Enter to send, Shift+Enter for new line)"
                class="min-h-[3rem] flex-1 rounded-xl border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent"
                x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.sendMessage(); }"
            ></textarea>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                class="self-end rounded-xl bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="sendMessage">Send</span>
                <span wire:loading wire:target="sendMessage">Sending...</span>
            </button>
        </form>
        @endif
    </div>

    @if ($showTemplates)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-lg rounded-xl border border-gw-accent/40 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gw-primary">Message Templates</h3>
                    <button type="button" wire:click="$set('showTemplates', false)" class="text-slate-400">✕</button>
                </div>
                <div class="max-h-96 space-y-2 overflow-y-auto p-5">
                    @foreach ($templates as $template)
                        <button type="button" wire:click="applyTemplate(@js($template->body))" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-left hover:bg-gw-accent-soft">
                            <p class="text-sm font-semibold text-gw-primary">{{ $template->title }}</p>
                            <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $template->body }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($showTaskModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-md rounded-xl border border-gw-accent/40 bg-white shadow-xl p-5 space-y-4">
                <h3 class="text-lg font-semibold text-gw-primary">Create Task from Message</h3>
                <input type="text" wire:model="taskTitle" placeholder="Task title" class="w-full rounded-lg border-slate-300 text-sm">
                <select wire:model="taskPriority" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
                <input type="date" wire:model="taskDueDate" class="w-full rounded-lg border-slate-300 text-sm">
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showTaskModal', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                    <button type="button" wire:click="createTaskFromMessage" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary">Create Task</button>
                </div>
            </div>
        </div>
    @endif
</main>
