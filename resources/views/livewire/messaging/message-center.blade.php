<div class="gw-messaging-center space-y-4">
    @if (session('message_status'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('message_status') }}
        </div>
    @endif

    @if ($isMessagingSuspended)
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">Messaging access suspended</p>
            <p class="mt-1">You cannot send messages or start new conversations at this time.</p>
            @if ($messagingSuspensionReason)
                <p class="mt-2 text-red-800"><span class="font-semibold">Reason:</span> {{ $messagingSuspensionReason }}</p>
            @endif
            <p class="mt-2 text-red-800">Contact your team administrator if you believe this is an error.</p>
        </div>
    @else
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-semibold">Business use only</p>
            <p class="mt-1">{{ $usagePolicyNotice }}</p>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Communication Center</p>
                    <h1 class="mt-2 text-2xl font-semibold">Messages & Collaboration</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">Direct messages, group conversations, mentoring threads, and team collaboration.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @unless ($isMessagingSuspended)
                        <button type="button" wire:click="$set('showNewConversation', true)" class="inline-flex items-center gap-1.5 rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">New Message</button>
                        @if ($canManageGroups)
                            <button type="button" wire:click="$set('showNewConversation', true)" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-4 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">Create Group</button>
                        @endif
                    @endunless
                </div>
            </div>
        </div>

        <div class="grid gap-3 border-t border-slate-200/80 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-tracker-stat-card label="Unread" :value="$stats['unread']" theme="amber" subtitle="Awaiting your reply" />
            <x-tracker-stat-card label="Active" :value="$stats['active']" theme="emerald" subtitle="Open conversations" />
            <x-tracker-stat-card label="Today" :value="$stats['today']" theme="cyan" subtitle="Messages sent today" />
            <x-tracker-stat-card label="Groups" :value="$stats['groups']" theme="violet" subtitle="Group threads" />
            <x-tracker-stat-card label="Archived" :value="$stats['archived']" theme="slate" subtitle="Stored conversations" />
            <x-tracker-stat-card label="Flagged" :value="$stats['flagged']" theme="red" subtitle="Marked for follow-up" />
        </div>
    </div>

    <section class="grid min-h-[36rem] gap-4 xl:grid-cols-[18rem_minmax(0,1fr)_18rem]">
        {{-- Conversation list --}}
        <aside class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[#F8FAFC] p-4">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search conversations..."
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach (['all' => 'All', 'direct' => 'Direct', 'group' => 'Groups', 'archived' => 'Archived', 'flagged' => 'Flagged'] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('listFilter', '{{ $key }}')"
                            class="rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide transition {{ $listFilter === $key ? 'bg-gw-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-gw-accent-soft' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="max-h-[32rem] overflow-y-auto divide-y divide-slate-100">
                @forelse ($conversations as $conversation)
                    <button
                        type="button"
                        wire:click="selectConversation({{ $conversation['id'] }})"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-gw-accent-soft {{ (int) $conversationId === (int) $conversation['id'] ? 'bg-gw-accent-soft' : '' }}"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gw-primary text-sm font-bold text-gw-accent">
                            @if ($conversation['photo_url'])
                                <img src="{{ $conversation['photo_url'] }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            @else
                                {{ str($conversation['name'])->substr(0, 1)->upper() }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gw-primary">{{ $conversation['name'] }}</span>
                                <span class="shrink-0 text-[0.65rem] text-slate-400">{{ $conversation['last_message_at'] }}</span>
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $conversation['role'] ?? ucfirst($conversation['type']) }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-600">{{ $conversation['last_message'] }}</p>
                        </div>
                        @if ($conversation['unread'])
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-gw-accent"></span>
                        @endif
                    </button>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-500">No conversations yet. Start a new message.</div>
                @endforelse
            </div>
        </aside>

        {{-- Chat window --}}
        @if ($selectedConversation)
            <livewire:messaging.conversation-panel
                :conversation-id="$selectedConversation->id"
                :is-messaging-suspended="$isMessagingSuspended"
                wire:key="conversation-panel-{{ $selectedConversation->id }}"
            />
        @else
            <main class="flex min-h-[36rem] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-1 flex-col items-center justify-center bg-slate-50 p-8 text-center">
                    <p class="text-sm font-semibold text-gw-primary">Select a conversation</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">Choose a thread from the left or start a new message with your sponsor, CFM, or team member.</p>
                @unless ($isMessagingSuspended)
                    <button type="button" wire:click="$set('showNewConversation', true)" class="mt-4 rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary">Start Conversation</button>
                @endunless
                </div>
            </main>
        @endif

        {{-- Context panel --}}
        <aside class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-[#F8FAFC] px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Context</p>
                <h3 class="text-sm font-semibold text-gw-primary">Profile & Progress</h3>
            </div>
            <div class="space-y-4 p-4">
                @if ($context['focus_user'] ?? null)
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gw-primary text-xl font-bold text-gw-accent">
                            {{ str($context['focus_user']['name'])->substr(0, 1)->upper() }}
                        </div>
                        <h4 class="mt-3 text-base font-semibold text-gw-primary">{{ $context['focus_user']['name'] }}</h4>
                        <p class="text-xs text-slate-500">{{ $context['focus_user']['role'] }}</p>
                    </div>
                    @if ($context['progress'] ?? null)
                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Trainee Progress</p>
                            @foreach ($context['progress'] as $item)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                    <div class="flex items-center justify-between text-xs font-semibold text-gw-primary">
                                        <span>{{ $item['label'] }}</span>
                                        <span>{{ $item['percent'] }}%</span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-gw-accent" style="width: {{ $item['percent'] }}%"></div>
                                    </div>
                                    <p class="mt-1 text-[0.65rem] text-slate-500">{{ $item['status'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                @foreach ($context['members'] ?? [] as $member)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="font-semibold text-gw-primary">{{ $member['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $member['role'] }}</p>
                        @if ($member['sponsor'])
                            <p class="mt-1 text-xs text-slate-500">Sponsor: {{ $member['sponsor'] }}</p>
                        @endif
                        @if ($member['mentor'])
                            <p class="text-xs text-slate-500">CFM: {{ $member['mentor'] }}</p>
                        @endif
                        <a href="{{ $member['profile_url'] }}" class="mt-2 inline-block text-xs font-semibold text-gw-accent-dark hover:text-gw-accent">View profile →</a>
                    </div>
                @endforeach

                @if (empty($context['members']) && empty($context['focus_user']))
                    <p class="text-sm text-slate-500">Open a conversation to see participant details and trainee progress.</p>
                @endif
            </div>
        </aside>
    </section>

    {{-- New conversation modal --}}
    @if ($showNewConversation)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-lg rounded-xl border border-gw-accent/40 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gw-primary">New Conversation</h3>
                    <button type="button" wire:click="$set('showNewConversation', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Find member</label>
                        <input type="search" wire:model.live.debounce.300ms="recipientSearch" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Search by name or email">
                    </div>
                    <div class="max-h-48 space-y-2 overflow-y-auto">
                        @foreach ($messageableUsers as $candidate)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-gw-accent-soft">
                                <input type="radio" wire:model="newRecipientId" value="{{ $candidate->id }}" class="text-gw-accent focus:ring-gw-accent">
                                <div>
                                    <p class="text-sm font-semibold text-gw-primary">{{ $candidate->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $candidate->topbarRankRoleLabel('Member') }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @if ($canManageGroups)
                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Or create group</p>
                            <input type="text" wire:model="groupName" placeholder="Group name" class="mt-2 w-full rounded-lg border-slate-300 text-sm">
                            <div class="mt-2 max-h-32 space-y-1 overflow-y-auto">
                                @foreach ($messageableUsers as $candidate)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="groupMemberIds" value="{{ $candidate->id }}" class="rounded text-gw-accent">
                                        {{ $candidate->name }}
                                    </label>
                                @endforeach
                            </div>
                            <button type="button" wire:click="createGroup" class="mt-3 rounded-lg border border-gw-primary px-4 py-2 text-sm font-semibold text-gw-primary">Create Group</button>
                        </div>
                    @endif
                    <button type="button" wire:click="startDirectConversation" class="w-full rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary">Start Direct Message</button>
                </div>
            </div>
        </div>
    @endif
</div>
