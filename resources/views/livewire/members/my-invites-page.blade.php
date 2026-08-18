@php
    $statusClasses = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'accepted' => 'bg-sky-100 text-sky-800',
        'expired' => 'bg-amber-100 text-amber-800',
        'revoked' => 'bg-slate-200 text-slate-700',
        'used' => 'bg-slate-200 text-slate-700',
    ];
    $usageLabels = [
        'active' => 'Unused',
        'accepted' => 'Signed up',
        'expired' => 'Expired',
        'revoked' => 'Deleted',
        'used' => 'Signed up',
    ];
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Recruiting</p>
                    <h1 class="mt-2 text-2xl font-semibold">My Invites</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                        Track every registration link you created, who signed up, and which links are still unused.
                    </p>
                </div>
                <a
                    href="{{ route('profile.edit') }}"
                    class="inline-flex items-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:border-gw-accent hover:bg-white/15"
                >
                    Back to My Profile
                </a>
            </div>
        </div>

        <div class="grid gap-3 border-t border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <button type="button" wire:click="$set('status', 'all')" @class([
                'rounded-lg border px-4 py-3 text-left transition',
                'border-gw-accent bg-gw-accent-soft' => $status === 'all',
                'border-slate-200 hover:border-gw-accent/50' => $status !== 'all',
            ])>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total</p>
                <p class="mt-1 text-2xl font-bold text-gw-primary">{{ number_format($stats['total']) }}</p>
            </button>
            <button type="button" wire:click="$set('status', 'unused')" @class([
                'rounded-lg border px-4 py-3 text-left transition',
                'border-gw-accent bg-gw-accent-soft' => $status === 'unused',
                'border-slate-200 hover:border-gw-accent/50' => $status !== 'unused',
            ])>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Unused</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($stats['unused']) }}</p>
            </button>
            <button type="button" wire:click="$set('status', 'signed_up')" @class([
                'rounded-lg border px-4 py-3 text-left transition',
                'border-gw-accent bg-gw-accent-soft' => $status === 'signed_up',
                'border-slate-200 hover:border-gw-accent/50' => $status !== 'signed_up',
            ])>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Signed up</p>
                <p class="mt-1 text-2xl font-bold text-sky-700">{{ number_format($stats['signed_up']) }}</p>
            </button>
            <button type="button" wire:click="$set('status', 'expired')" @class([
                'rounded-lg border px-4 py-3 text-left transition',
                'border-gw-accent bg-gw-accent-soft' => $status === 'expired',
                'border-slate-200 hover:border-gw-accent/50' => $status !== 'expired',
            ])>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Expired</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($stats['expired']) }}</p>
            </button>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Create invite</p>
            <h2 class="mt-1 text-lg font-semibold text-gw-primary">New registration link</h2>
            <p class="mt-2 text-sm text-slate-600">
                Generate a single-use link. Recruits who register with it will be placed under you as sponsor.
            </p>

            @if ($statusMessage)
                <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
                    {{ $statusMessage }}
                </div>
            @endif

            <form wire:submit="createInvitation" class="mt-5 space-y-4">
                <div>
                    <x-input-label for="my_invites_email" value="Invitee email (optional)" />
                    <x-text-input id="my_invites_email" wire:model="inviteEmail" type="email" class="mt-1 block w-full" placeholder="new.member@example.com" />
                    <x-input-error class="mt-2" :messages="$errors->get('inviteEmail')" />
                </div>
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="createInvitation"
                >
                    <span wire:loading.remove wire:target="createInvitation">Generate Invite Link</span>
                    <span wire:loading wire:target="createInvitation">Generating…</span>
                </button>
            </form>

            @if ($latestInvitationUrl)
                <div
                    x-data="{ copied: false, link: @js($latestInvitationUrl) }"
                    class="mt-5 rounded-md border border-gw-accent/40 bg-gw-accent/10 p-4"
                >
                    <p class="text-sm font-semibold text-gw-primary">New invitation link</p>
                    <p class="mt-2 break-all rounded-md bg-white p-3 text-xs text-slate-700">{{ $latestInvitationUrl }}</p>
                    <button
                        type="button"
                        class="mt-3 rounded-md bg-gw-primary px-3 py-2 text-xs font-semibold text-white hover:bg-gw-primary-hover"
                        x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"
                    >
                        <span x-show="! copied">Copy Link</span>
                        <span x-show="copied" x-cloak>Copied</span>
                    </button>
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gw-primary">Invitation list</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            @if ($status === 'unused')
                                Links that have not been used for registration yet.
                            @elseif ($status === 'signed_up')
                                Invites that resulted in a completed signup.
                            @else
                                All registration invitations you have created.
                            @endif
                        </p>
                    </div>
                    <div class="w-full sm:max-w-xs">
                        <label for="my_invites_search" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input
                            id="my_invites_search"
                            type="search"
                            wire:model.live.debounce.300ms="q"
                            placeholder="Search code, email, member…"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Invite</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Signup / outcome</th>
                            <th class="hidden px-4 py-3 md:table-cell">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($invitations as $invitation)
                            @php
                                $statusKey = $invitation->statusKey();
                            @endphp
                            <tr wire:key="invite-{{ $invitation->id }}">
                                <td class="px-4 py-3 align-top">
                                    <p class="font-mono text-xs font-semibold text-gw-primary">{{ $invitation->code }}</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $invitation->recipientLabel() }}</p>
                                    <p class="mt-1 break-all text-[11px] text-slate-500">{{ $invitation->invitationUrl() }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$statusKey] ?? $statusClasses['used'] }}">
                                        {{ $usageLabels[$statusKey] ?? $invitation->statusLabel() }}
                                    </span>
                                    @if ($statusKey === 'active')
                                        <p class="mt-1 text-[11px] text-emerald-700">Never used yet</p>
                                    @elseif ($statusKey === 'accepted' && $invitation->accepted_at)
                                        <p class="mt-1 text-[11px] text-slate-500">{{ $invitation->accepted_at->format('M j, Y') }}</p>
                                    @elseif ($statusKey === 'expired' && $invitation->expires_at)
                                        <p class="mt-1 text-[11px] text-slate-500">Expired {{ $invitation->expires_at->format('M j, Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    <p class="font-medium text-gw-primary">{{ $invitation->outcomeDescription() }}</p>
                                    @if ($invitation->acceptedBy)
                                        <p class="mt-1 text-xs text-slate-500">{{ $invitation->acceptedBy->email }}</p>
                                    @elseif ($statusKey === 'active')
                                        <p class="mt-1 text-xs text-slate-500">
                                            Expires {{ $invitation->expires_at?->format('M j, Y') ?? 'never' }}
                                        </p>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 align-top text-xs text-slate-600 md:table-cell">
                                    <p>{{ $invitation->created_at?->format('M j, Y g:i A') }}</p>
                                    @if ($invitation->last_emailed_at)
                                        <p class="mt-1 text-slate-500">Emailed {{ $invitation->last_emailed_at->diffForHumans() }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-right">
                                    @if ($invitation->isAvailable())
                                        <div
                                            class="inline-flex flex-wrap justify-end gap-2"
                                            x-data="{ copied: false, link: @js($invitation->invitationUrl()) }"
                                        >
                                            <button
                                                type="button"
                                                class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gw-primary hover:border-gw-accent"
                                                x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"
                                                x-text="copied ? 'Copied' : 'Copy'"
                                            ></button>
                                            <button
                                                type="button"
                                                class="rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50"
                                                wire:click="destroyInvitation({{ $invitation->id }})"
                                                wire:confirm="Delete this unused invitation link?"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    @elseif ($invitation->acceptedBy)
                                        <a
                                            href="{{ route('team.member.profile', $invitation->acceptedBy) }}"
                                            class="inline-flex rounded-md border border-gw-primary bg-gw-primary px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white hover:bg-gw-primary-hover"
                                        >
                                            View Profile
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <p class="text-sm font-semibold text-gw-primary">No invitations found</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if (filled($q) || $status !== 'all')
                                            Try clearing search or choosing a different status filter.
                                        @else
                                            Generate your first registration link to start inviting members.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($invitations->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $invitations->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
