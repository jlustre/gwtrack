<div class="space-y-6">
    @if (session('fna_invite_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('fna_invite_status') }}
        </div>
    @endif

    @if ($createdSecurityCode && $createdInviteUrl)
        <div
            class="rounded-xl border-2 border-gw-accent bg-gw-accent-soft p-5"
            x-data="{ copied: false, copyInviteLink() { navigator.clipboard.writeText(@js($createdInviteUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }"
        >
            <h3 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">Invite created — share these now</h3>
            <p class="mt-2 text-sm text-slate-700">The security code is shown only once. Copy both the link and code for your {{ $recipientContext === 'member' ? 'member' : 'prospect or client' }}.</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-semibold text-slate-600">Invite link</dt>
                    <dd class="mt-1 flex flex-wrap items-start gap-2">
                        <span class="min-w-0 flex-1 break-all rounded-lg bg-white px-3 py-2 font-mono text-xs text-gw-primary">{{ $createdInviteUrl }}</span>
                        <button
                            type="button"
                            x-on:click="copyInviteLink()"
                            class="shrink-0 rounded-lg border border-gw-accent bg-white px-3 py-2 text-xs font-semibold text-gw-accent-dark hover:bg-[#FFF3D6]"
                            x-text="copied ? 'Copied' : 'Copy link'"
                        ></button>
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-600">Security code</dt>
                    <dd class="mt-1 inline-block rounded-lg bg-gw-primary px-4 py-2 text-2xl font-bold tracking-[0.35em] text-gw-accent">{{ $createdSecurityCode }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @if ($needsCfmForInvite)
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gw-primary">FNA client portal invite</h3>
            <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Insurance license required</p>
                <p class="mt-1">Only a licensed insurance writing agent can create or send FNA client portal invites. Share this prospect with your Certified Field Mentor (CFM) so they can send the invite on your behalf.</p>
            </div>

            @if (! $cfm)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">No CFM assigned</p>
                    <p class="mt-1">You do not have a CFM on your profile yet. Ask your sponsor or agency owner to assign a Certified Field Mentor before you can request an FNA portal invite.</p>
                </div>
            @elseif ($cfmShareActive)
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <p class="font-semibold">Shared with {{ $cfm->name }}</p>
                    <p class="mt-1">Your CFM can now create and send the FNA client portal invite for this prospect. They will be notified through shared prospect access.</p>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">
                    Grant <strong>{{ $cfm->name }}</strong> full collaboration access to this prospect so they can review the profile and send the secure FNA portal invite.
                </p>
                <button
                    type="button"
                    wire:click="grantCfmAccess"
                    wire:confirm="Share this prospect with {{ $cfm->name }} so they can send the FNA client portal invite?"
                    class="mt-4 rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
                >
                    Share with CFM for FNA invite
                </button>
                @error('cfm') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @endif
        </div>
    @endif

    @if ($canSend)
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gw-primary">Send FNA client portal invite</h3>
            @if ($recipientContext === 'member')
                <p class="mt-1 text-sm text-slate-600">This GWTracks member can complete the FNA through the secure client portal. The portal is separate from their member login.</p>
            @else
                <p class="mt-1 text-sm text-slate-600">Your prospect or member can complete the FNA online. Non-members do not need a GWTracks account.</p>
            @endif

            @if ($prospectMissingEmail)
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">No email on file for this prospect</p>
                    <p class="mt-1">Add an email below or update the prospect record before using <strong>Create &amp; email invite</strong>. You can still create a link to share manually.</p>
                </div>
            @endif

            <form wire:submit="sendInvite" class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Recipient name</label>
                    <input type="text" wire:model="recipient_name" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @error('recipient_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                        <input type="email" wire:model="recipient_email" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('recipient_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Phone</label>
                        <input type="text" wire:model="recipient_phone" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('recipient_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Personal message (optional)</label>
                    <textarea wire:model="personal_message" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Create invite link
                    </button>
                    <button
                        type="button"
                        wire:click="sendInviteAndEmail"
                        @disabled($prospectMissingEmail && blank($recipient_email))
                        class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Create &amp; email invite
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-gw-primary">Client portal invites</h3>
        @if ($invites->isEmpty())
            <p class="mt-3 text-sm text-slate-600">No client portal invites yet.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($invites as $invite)
                    <article class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $invite->recipient_name }}</p>
                                <p class="mt-1 text-slate-600">{{ $invite->recipientTypeLabel() }} · {{ $invite->statusLabel() }} · {{ $invite->fnaRecord?->completeness_score ?? 0 }}% complete</p>
                                @if ($invite->last_emailed_at)
                                    <p class="mt-1 text-xs text-slate-500">Emailed {{ $invite->last_emailed_at->format('M j, Y g:i A') }}</p>
                                @endif
                                @if ($invite->last_saved_at)
                                    <p class="mt-1 text-xs text-slate-500">Last saved {{ $invite->last_saved_at->format('M j, Y g:i A') }}</p>
                                @endif
                            </div>
                            @if ($invite->isUsable())
                                <button type="button" wire:click="revokeInvite('{{ $invite->id }}')" wire:confirm="Revoke this invite?" class="text-xs font-semibold text-red-700 hover:underline">Revoke</button>
                            @endif
                        </div>
                        @if ($invite->isUsable())
                            @php($inviteUrl = $invite->inviteUrl())
                            <div
                                class="mt-2 flex flex-wrap items-start gap-2"
                                x-data="{ copied: false, copyInviteLink() { navigator.clipboard.writeText(@js($inviteUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }"
                            >
                                <p class="min-w-0 flex-1 break-all font-mono text-xs text-gw-accent-dark">{{ $inviteUrl }}</p>
                                <button
                                    type="button"
                                    x-on:click="copyInviteLink()"
                                    class="shrink-0 text-xs font-semibold text-gw-accent-dark hover:underline"
                                    x-text="copied ? 'Copied' : 'Copy link'"
                                ></button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
