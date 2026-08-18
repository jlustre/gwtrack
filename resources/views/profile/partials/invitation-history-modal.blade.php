@php
    $statusClasses = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'accepted' => 'bg-sky-100 text-sky-800',
        'expired' => 'bg-amber-100 text-amber-800',
        'revoked' => 'bg-slate-200 text-slate-700',
        'used' => 'bg-slate-200 text-slate-700',
    ];
    $highlightInvitationId = $highlightInvitationId ?? null;
    $useLivewire = $useLivewire ?? false;
    $invitationStatusValue = $useLivewire
        ? ($invitationStatus ?? null)
        : session('status');
@endphp

<template x-teleport="body">
<div
    x-show="historyOpen"
    x-cloak
    x-on:keydown.escape.window="historyOpen = false"
    class="fixed inset-0 z-[200] overflow-y-auto px-4 py-8 sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="invitation-history-title"
>
    <div class="fixed inset-0 bg-slate-950/60" x-on:click="historyOpen = false" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto w-full max-w-lg rounded-lg bg-white shadow-xl">
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Registration Invitations</p>
                <h3 id="invitation-history-title" class="mt-0.5 text-lg font-semibold text-gw-primary">Invitation Link Log</h3>
            </div>
            <button type="button" class="shrink-0 rounded-md p-1 text-slate-500 hover:bg-slate-100" x-on:click="historyOpen = false" aria-label="Close">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="max-h-[min(70vh,36rem)] space-y-3 overflow-y-auto p-4">
            @if ($invitationStatusValue === 'invitation-email-sent')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    Invitation email sent.
                </div>
            @elseif ($invitationStatusValue === 'invitation-deleted')
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700">
                    Invitation deleted. You can create a new link for that email if needed.
                </div>
            @endif

            @forelse ($invitationHistory as $invitation)
                @php
                    $emailPreview = $invitationEmails->get($invitation->id);
                    $statusKey = $invitation->statusKey();
                @endphp
                <div
                    x-data="{
                        mailOpen: @js($highlightInvitationId === $invitation->id && $invitation->isAvailable()),
                        copied: false,
                        link: @js($invitation->invitationUrl()),
                    }"
                    @class([
                        'rounded-lg border bg-slate-50 p-3',
                        'border-gw-accent ring-2 ring-gw-accent/30' => $highlightInvitationId === $invitation->id,
                        'border-slate-200' => $highlightInvitationId !== $invitation->id,
                    ])
                    @if ($highlightInvitationId === $invitation->id)
                        x-init="$nextTick(() => $el.scrollIntoView({ block: 'center', behavior: 'smooth' }))"
                    @endif
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate font-mono text-xs font-semibold text-gw-primary">{{ $invitation->code }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-semibold {{ $statusClasses[$statusKey] ?? $statusClasses['used'] }}">
                            {{ $invitation->statusLabel() }}
                        </span>
                    </div>

                    <dl class="mt-2.5 space-y-1.5 text-xs">
                        <div class="flex gap-2">
                            <dt class="w-16 shrink-0 font-semibold uppercase tracking-wide text-slate-500">To</dt>
                            <dd class="min-w-0 flex-1 break-words font-medium text-gw-primary">{{ $invitation->recipientLabel() }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-16 shrink-0 font-semibold uppercase tracking-wide text-slate-500">Expires</dt>
                            <dd class="min-w-0 flex-1 text-slate-700">{{ $invitation->expires_at?->format('M j, Y g:i A') ?? 'No expiration' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-16 shrink-0 font-semibold uppercase tracking-wide text-slate-500">Created</dt>
                            <dd class="min-w-0 flex-1 text-slate-700">{{ $invitation->created_at?->format('M j, Y g:i A') }}</dd>
                        </div>
                        @if ($invitation->last_emailed_at)
                            <div class="flex gap-2">
                                <dt class="w-16 shrink-0 font-semibold uppercase tracking-wide text-slate-500">Emailed</dt>
                                <dd class="min-w-0 flex-1 text-slate-700">
                                    {{ $invitation->last_emailed_at->format('M j, g:i A') }}
                                    <span class="text-slate-500">({{ $invitation->last_emailed_at->diffForHumans() }})</span>
                                </dd>
                            </div>
                        @endif
                        <div class="flex gap-2">
                            <dt class="w-16 shrink-0 font-semibold uppercase tracking-wide text-slate-500">Outcome</dt>
                            <dd class="min-w-0 flex-1 text-slate-700">{{ $invitation->outcomeDescription() }}</dd>
                        </div>
                    </dl>

                    <div class="mt-2.5 break-all rounded border border-slate-200 bg-white px-2 py-1.5 font-mono text-[0.65rem] leading-4 text-slate-600">
                        {{ $invitation->invitationUrl() }}
                    </div>

                    @if ($invitation->isAvailable())
                        <div class="mt-2.5 flex gap-2">
                            <button
                                type="button"
                                class="flex-1 rounded-md bg-gw-primary px-2 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-white hover:bg-gw-primary-hover"
                                x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"
                                x-text="copied ? 'Copied' : 'Copy'"
                            ></button>

                            @if ($emailPreview)
                                <button type="button" class="flex-1 rounded-md bg-gw-accent px-2 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-gw-primary hover:bg-gw-accent-hover" x-on:click="mailOpen = true">
                                    Mail
                                </button>
                            @endif

                            @if ($useLivewire)
                                <button
                                    type="button"
                                    class="flex-1 rounded-md border border-red-300 bg-white px-2 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50"
                                    wire:click="destroyInvitation({{ $invitation->id }})"
                                    wire:confirm="Delete this invitation link?"
                                >
                                    Delete
                                </button>
                            @else
                                <form method="POST" action="{{ route('profile.invitations.destroy', $invitation) }}" class="flex-1" onsubmit="return confirm('Delete this invitation link?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-md border border-red-300 bg-white px-2 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="mt-2.5 text-center text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">No actions available</p>
                    @endif

                    @if ($invitation->isAvailable() && $emailPreview)
                        @include('profile.partials.invitation-email-modal', [
                            'invitation' => $invitation,
                            'emailPreview' => $emailPreview,
                            'modalState' => 'mailOpen',
                            'inputPrefix' => 'history_'.$invitation->id,
                            'useLivewire' => $useLivewire,
                        ])
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-gw-primary">No invitation links yet</p>
                    <p class="mt-1 text-xs text-slate-600">Generate a link from the profile sidebar.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 px-4 py-3 text-right">
            <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" x-on:click="historyOpen = false">
                Close
            </button>
        </div>
    </div>
</div>
</template>
