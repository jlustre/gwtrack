@php
    $profile = $user->profile;
    $newInvitation = $isOwnProfile && $latestInvitationId
        ? $invitationHistory->firstWhere('id', $latestInvitationId)
        : null;
    $newInvitationEmail = $newInvitation ? $invitationEmails->get($newInvitation->id) : null;
@endphp

<div>
<div
    @if ($isOwnProfile)
        x-data="{ historyOpen: @entangle('historyOpen') }"
        x-effect="document.documentElement.classList.toggle('overflow-hidden', historyOpen)"
    @endif
>
    <div class="space-y-6">
        @if (! $isOwnProfile)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Team Member Profile</p>
                    <p class="mt-1 text-sm text-slate-600">Viewing {{ $user->name }}&rsquo;s profile and progress tabs.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @can('create', \App\Models\FnaClientInvite::class)
                        @if (auth()->user()->profile?->license_number)
                            <button type="button" onclick="Livewire.dispatch('open-fna-client-invite-member-modal', { memberUserId: {{ $user->id }} })" class="inline-flex items-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                                Send FNA Link
                            </button>
                        @endif
                    @endcan
                    <a href="{{ route('team.index') }}" class="inline-flex items-center rounded-lg border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted">
                        Back to Team Command Center
                    </a>
                </div>
            </div>
        @endif

        @include('profile.partials.member-header', [
            'user' => $user,
            'badge' => $isOwnProfile ? 'Member Profile' : 'Team Member Profile',
            'showGwDetails' => $isOwnProfile,
        ])

        <div class="grid gap-6 {{ $isOwnProfile ? 'xl:grid-cols-3' : '' }}">
            <div class="{{ $isOwnProfile ? 'xl:col-span-2' : '' }}">
                @include('profile.partials.member-tabs')
            </div>

            @if ($isOwnProfile)
            <aside class="space-y-6">
                <section class="rounded-lg border border-gw-accent/40 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Invite A Member</p>
                            <h2 class="mt-1 text-xl font-semibold text-gw-primary">Registration Link</h2>
                        </div>
                        <span class="rounded-full bg-gw-primary px-3 py-1 text-xs font-semibold text-white">Sponsor</span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Create a single-use invitation link. The new member will be registered under you as their sponsor.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            href="{{ route('invites.index') }}"
                            class="rounded-md border border-gw-primary bg-gw-primary px-3 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
                        >
                            My Invites
                            @if ($invitationHistory->isNotEmpty())
                                <span class="ml-1 rounded-full bg-gw-accent px-2 py-0.5 text-xs text-gw-primary">{{ $invitationHistory->count() }}</span>
                            @endif
                        </a>
                        <button
                            type="button"
                            class="rounded-md border border-gw-accent bg-gw-accent-soft px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted"
                            wire:click="openInvitationHistory"
                        >
                            View Invitation Log
                            @if ($invitationHistory->isNotEmpty())
                                <span class="ml-1 rounded-full bg-gw-primary px-2 py-0.5 text-xs text-gw-accent">{{ $invitationHistory->count() }}</span>
                            @endif
                        </button>
                    </div>

                    <form wire:submit="createInvitation" class="mt-5 space-y-4">
                        <div>
                            <x-input-label for="invite_email" :value="__('Invitee Email Optional')" />
                            <x-text-input id="invite_email" wire:model="inviteEmail" type="email" class="mt-1 block w-full" placeholder="new.member@example.com" />
                            <x-input-error class="mt-2" :messages="$errors->get('inviteEmail')" />
                        </div>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:opacity-60"
                            wire:loading.attr="disabled"
                            wire:target="createInvitation"
                        >
                            <span wire:loading.remove wire:target="createInvitation">{{ __('Generate Invite Link') }}</span>
                            <span wire:loading wire:target="createInvitation">Generating…</span>
                        </button>
                    </form>

                    @if ($latestInvitationUrl)
                        <div x-data="{ copied: false, mailOpen: false, link: @js($latestInvitationUrl) }" class="mt-5 rounded-md border border-gw-accent/40 bg-gw-accent/10 p-4">
                            <div class="text-sm font-semibold text-gw-primary">New invitation link</div>
                            <div class="mt-3 break-all rounded-md bg-white p-3 text-sm text-slate-700">{{ $latestInvitationUrl }}</div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="rounded-md bg-gw-primary px-3 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover" x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="! copied">Copy Link</span>
                                    <span x-show="copied">Copied</span>
                                </button>

                                @if ($newInvitation && $newInvitationEmail)
                                    <button type="button" class="rounded-md bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover" x-on:click="mailOpen = true">
                                        Mail Link
                                    </button>
                                @endif
                            </div>

                            @if ($newInvitation && $newInvitationEmail)
                                @include('profile.partials.invitation-email-modal', [
                                    'invitation' => $newInvitation,
                                    'emailPreview' => $newInvitationEmail,
                                    'modalState' => 'mailOpen',
                                    'inputPrefix' => 'new',
                                    'useLivewire' => true,
                                ])
                            @endif
                        </div>
                    @endif

                    @if ($invitationStatus === 'invitation-email-sent')
                        <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                            Invitation email sent. Open the invitation log for details.
                        </div>
                    @endif

                    @if ($invitationStatus === 'invitation-deleted')
                        <div class="mt-5 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700">
                            Invitation link deleted. You can create a new link for that email if they have not registered yet.
                        </div>
                    @endif

                </section>

                @include('profile.partials.gw-details-panel', [
                    'user' => $user,
                    'profile' => $profile,
                    'gwDetailsFeedback' => $gwDetailsFeedback,
                    'useLivewire' => true,
                ])

                @include('profile.partials.profile-completion-panel', [
                    'profileCompletion' => $profileCompletion,
                    'isOwnProfile' => $isOwnProfile,
                    'useLivewire' => true,
                ])
            </aside>
            @endif
        </div>
    </div>

    @if ($isOwnProfile)
        @include('profile.partials.invitation-history-modal', [
            'invitationHistory' => $invitationHistory,
            'invitationEmails' => $invitationEmails,
            'highlightInvitationId' => $latestInvitationId,
            'invitationStatus' => $invitationStatus,
            'useLivewire' => true,
        ])
    @endif
</div>

@if ($isOwnProfile)
    @include('partials.rich-text-editor-scripts')
@else
    @can('create', \App\Models\FnaClientInvite::class)
        <livewire:fna.fna-client-invite-modal />
    @endcan
@endif
</div>
