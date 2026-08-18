@php
    $useLivewire = $useLivewire ?? false;
    $gwDetailsFeedback = $gwDetailsFeedback ?? session('gw_details_feedback');
@endphp

<section
    class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm"
    x-data="{
        shareOpen: false,
        copied: false,
        recipientEmail: '',
        inviteLink: @js($useLivewire ? ($gwInviteLink ?? '') : old('gw_invite_link', $profile?->gw_invite_link ?? '')),
        senderName: @js($user->name),
        hasInviteLink() {
            return this.inviteLink.trim().length > 0;
        },
        inviteLinkValue() {
            return this.inviteLink.trim();
        },
        toggleSharePanel() {
            if (! this.hasInviteLink()) {
                return;
            }
            this.shareOpen = ! this.shareOpen;
            if (this.shareOpen) {
                this.$nextTick(() => this.$refs.recipientEmail?.focus());
            }
        },
        copyInviteLink() {
            const link = this.inviteLinkValue();
            if (! link) {
                return;
            }
            navigator.clipboard.writeText(link).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        },
        mailtoHref() {
            const link = this.inviteLinkValue();
            if (! link) {
                return '#';
            }
            const body = `Hi,\n\nI'd like to invite you to explore a career with Greatway Financial Group. Use my personal invite link:\n\n${link}\n\nBest,\n${this.senderName}`;
            const params = new URLSearchParams({
                subject: 'Join me at Greatway Financial Group',
                body,
            });
            const email = this.recipientEmail.trim();
            return email
                ? `mailto:${encodeURIComponent(email)}?${params.toString()}`
                : `mailto:?${params.toString()}`;
        },
    }"
>
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Greatway Financial Group</p>
        <h2 class="mt-1 text-lg font-semibold text-gw-primary">GW Details</h2>
    </div>
    <p class="mt-3 text-sm leading-6 text-slate-600">
        Save your Greatway Associate ID and personal recruitment invite link for quick reference when sharing with prospects.
    </p>

    @if ($gwDetailsFeedback)
        <div @class([
            'mt-4 rounded-md border px-3 py-3 text-sm font-semibold',
            'border-emerald-200 bg-emerald-50 text-emerald-700' => ($gwDetailsFeedback['type'] ?? '') === 'success',
            'border-red-200 bg-red-50 text-red-700' => ($gwDetailsFeedback['type'] ?? '') !== 'success',
        ]) role="alert">
            {{ $gwDetailsFeedback['message'] }}
        </div>
    @elseif (session('status') === 'gw-invite-link-saved')
        <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">
            GW details saved.
        </div>
    @endif

    @if ($errors->has('gw_associate_id') || $errors->has('gw_invite_link') || $errors->has('gwAssociateId') || $errors->has('gwInviteLink'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
            <p class="font-semibold text-red-900">Could not save GW details</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach (['gw_associate_id', 'gw_invite_link', 'gwAssociateId', 'gwInviteLink'] as $gwField)
                    @foreach ($errors->get($gwField) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <form
        @if ($useLivewire)
            wire:submit="saveGwDetails"
        @else
            method="POST"
            action="{{ route('profile.invite-link.update') }}"
        @endif
        class="mt-5 space-y-4"
    >
        @unless ($useLivewire)
            @csrf
            @method('PATCH')
        @endunless

        <div>
            <x-input-label for="gw_associate_id" :value="__('GW Associate ID')" />
            @if ($useLivewire)
                <x-text-input
                    id="gw_associate_id"
                    wire:model="gwAssociateId"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="GW-1001"
                />
                <x-input-error class="mt-2" :messages="$errors->get('gwAssociateId')" />
            @else
                <x-text-input
                    id="gw_associate_id"
                    name="gw_associate_id"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('gw_associate_id', $profile?->gw_associate_id)"
                    placeholder="GW-1001"
                />
                <x-input-error class="mt-2" :messages="$errors->get('gw_associate_id')" />
            @endif
        </div>

        <div>
            <div class="flex items-center justify-between gap-2">
                <x-input-label for="gw_invite_link" :value="__('Greatway invite URL')" />
                <button
                    type="button"
                    class="gw-icon-btn"
                    x-on:click="toggleSharePanel()"
                    :disabled="! hasInviteLink()"
                    :title="hasInviteLink() ? 'Share invite link' : 'Enter and save an invite URL first'"
                    aria-label="Share Greatway invite link"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </button>
            </div>
            @if ($useLivewire)
                <x-text-input
                    id="gw_invite_link"
                    wire:model="gwInviteLink"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="https://greatwayfinancial.com/invite/..."
                    inputmode="url"
                    autocomplete="url"
                    x-on:input="inviteLink = $event.target.value; shareOpen = false; copied = false"
                />
                <x-input-error class="mt-2" :messages="$errors->get('gwInviteLink')" />
            @else
                <x-text-input
                    id="gw_invite_link"
                    name="gw_invite_link"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('gw_invite_link', $profile?->gw_invite_link)"
                    placeholder="https://greatwayfinancial.com/invite/..."
                    inputmode="url"
                    autocomplete="url"
                    x-on:input="inviteLink = $event.target.value; shareOpen = false; copied = false"
                />
                <x-input-error class="mt-2" :messages="$errors->get('gw_invite_link')" />
            @endif

            <div
                x-show="shareOpen"
                x-cloak
                x-transition
                class="mt-3 rounded-lg border border-gw-accent/30 bg-gw-accent-soft/60 p-4"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Share invite link</p>
                <p class="mt-1 text-xs text-slate-600">Copy the link or open your email app with a pre-filled message.</p>

                <div class="mt-3">
                    <x-input-label for="gw_invite_recipient_email" value="Recipient email (optional)" />
                    <x-text-input
                        id="gw_invite_recipient_email"
                        type="email"
                        class="mt-1 block w-full"
                        x-model="recipientEmail"
                        x-ref="recipientEmail"
                        placeholder="prospect@example.com"
                        autocomplete="email"
                    />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gw-primary bg-gw-primary px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-gw-primary-hover"
                        x-on:click="copyInviteLink()"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
                        <span x-text="copied ? 'Copied' : 'Copy link'"></span>
                    </button>
                    <a
                        :href="mailtoHref()"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent bg-gw-accent px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gw-primary transition hover:bg-gw-accent-hover"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 4h16v16H4z" />
                            <path d="m22 6-10 7L2 6" />
                        </svg>
                        Open in email
                    </a>
                    <button
                        type="button"
                        class="text-xs font-semibold text-slate-600 hover:text-gw-primary"
                        x-on:click="shareOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        @if ($useLivewire)
            <button
                type="submit"
                class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:opacity-60"
                wire:loading.attr="disabled"
                wire:target="saveGwDetails"
            >
                <span wire:loading.remove wire:target="saveGwDetails">{{ __('Save GW Details') }}</span>
                <span wire:loading wire:target="saveGwDetails">Saving…</span>
            </button>
        @else
            <x-primary-button>{{ __('Save GW Details') }}</x-primary-button>
        @endif
    </form>
</section>
