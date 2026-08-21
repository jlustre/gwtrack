<div>
    @if ($canInvite)
        <div
            @class([
                'fixed inset-0 z-[60] flex items-center justify-center p-4',
                'hidden' => ! $show,
            ])
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-member-modal-title"
            @if (! $show) aria-hidden="true" @endif
        >
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

            <div class="relative z-10 flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl">
                <div class="border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Sponsor Invite</p>
                            <h3 id="invite-member-modal-title" class="mt-1 text-lg font-semibold">Invite New Member</h3>
                            <p class="mt-1 text-sm text-slate-300">Generate a single-use registration link and code. New members register under you as their sponsor.</p>
                        </div>
                        <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6">
                    @unless ($invitationUrl)
                        <form wire:submit="generate" class="space-y-4">
                            <div>
                                <label for="invite-member-email" class="text-sm font-semibold text-gw-primary">Invitee email (optional)</label>
                                <input
                                    id="invite-member-email"
                                    type="email"
                                    wire:model="email"
                                    placeholder="new.member@example.com"
                                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                                <p class="mt-1 text-xs text-slate-500">Leave blank to create an open link for any email address.</p>
                                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" wire:click="close" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="generate"
                                    class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="generate">Generate invite</span>
                                    <span wire:loading wire:target="generate">Generating…</span>
                                </button>
                            </div>
                        </form>
                    @else
                        <div
                            x-data="{
                                linkCopied: false,
                                codeCopied: false,
                                copy(value, field) {
                                    navigator.clipboard.writeText(value);
                                    this[field] = true;
                                    setTimeout(() => { this[field] = false; }, 2000);
                                },
                            }"
                            class="space-y-4"
                        >
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                @if ($loadedExistingInvitation)
                                    Pending invitation ready to email. Confirm the recipient address below and send the registration link.
                                @else
                                    Invitation created. Share the link and registration code with your invitee.
                                @endif
                                @if ($expiresAtLabel)
                                    <span class="mt-1 block text-xs text-emerald-700">Expires {{ $expiresAtLabel }}</span>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Registration link</p>
                                <p class="mt-2 break-all rounded-md border border-slate-200 bg-slate-50 p-3 font-mono text-xs text-gw-primary">{{ $invitationUrl }}</p>

                                <div class="mt-3">
                                    <label for="invite-member-recipient-email" class="text-sm font-semibold text-gw-primary">
                                        Recipient email
                                        <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="invite-member-recipient-email"
                                        type="email"
                                        wire:model="recipientEmail"
                                        placeholder="new.member@example.com"
                                        class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                    >
                                    <p class="mt-1 text-xs text-slate-500">Required to email the invitation link.</p>
                                    @error('recipientEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                @if ($emailSentMessage)
                                    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
                                        {{ $emailSentMessage }}
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md bg-gw-primary px-3 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
                                        x-on:click="copy(@js($invitationUrl), 'linkCopied')"
                                    >
                                        <span x-show="! linkCopied">Copy link</span>
                                        <span x-show="linkCopied">Copied</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="sendEmail"
                                        wire:loading.attr="disabled"
                                        wire:target="sendEmail"
                                        class="rounded-md bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="sendEmail">Email link</span>
                                        <span wire:loading wire:target="sendEmail">Sending…</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Registration code</p>
                                <p class="mt-2 rounded-md border border-gw-accent/40 bg-gw-accent-soft p-3 font-mono text-sm font-bold tracking-wide text-gw-primary">{{ $invitationCode }}</p>
                                <button
                                    type="button"
                                    class="mt-2 rounded-md border border-gw-accent bg-gw-accent-soft px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted"
                                    x-on:click="copy(@js($invitationCode), 'codeCopied')"
                                >
                                    <span x-show="! codeCopied">Copy code</span>
                                    <span x-show="codeCopied">Copied</span>
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                                @if ($invitationUrl && ! $emailSentMessage)
                                    <button
                                        type="button"
                                        wire:click="deleteInvitation"
                                        wire:confirm="Delete this invitation link? The related task will be completed."
                                        wire:loading.attr="disabled"
                                        wire:target="deleteInvitation"
                                        class="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="deleteInvitation">Delete link</span>
                                        <span wire:loading wire:target="deleteInvitation">Deleting…</span>
                                    </button>
                                @else
                                    <span></span>
                                @endif

                                <a
                                    href="{{ route('profile.edit', ['open_invitations' => 1]) }}"
                                    class="text-sm font-semibold text-gw-primary hover:text-gw-accent"
                                >
                                    View invitation log
                                </a>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="close"
                                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Done
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="startAnother"
                                        class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover"
                                    >
                                        Generate another
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    @endif
</div>
