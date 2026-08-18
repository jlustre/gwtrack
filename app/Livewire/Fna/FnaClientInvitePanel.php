<?php

namespace App\Livewire\Fna;

use App\Models\FnaClientInvite;
use App\Models\FnaRecord;
use App\Models\Prospect;
use App\Models\ProspectSharePermission;
use App\Models\User;
use App\Services\Fna\FnaClientInviteRecipientService;
use App\Services\Fna\FnaClientInviteService;
use App\Services\Prospects\ProspectShareService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FnaClientInvitePanel extends Component
{
    public ?Prospect $prospect = null;

    public ?User $recipientMember = null;

    public ?FnaRecord $fna = null;

    public string $recipient_name = '';

    public string $recipient_email = '';

    public string $recipient_phone = '';

    public ?string $personal_message = null;

    public ?string $createdSecurityCode = null;

    public ?string $createdInviteUrl = null;

    public function mount(?Prospect $prospect = null, ?User $recipientMember = null, ?FnaRecord $fna = null): void
    {
        $this->prospect = $prospect;
        $this->recipientMember = $recipientMember;
        $this->fna = $fna;

        if ($prospect) {
            $this->authorize('requestFnaClientPortal', $prospect);
            $this->recipient_name = $prospect->displayName();
            $this->recipient_email = (string) ($prospect->email ?? '');
            $this->recipient_phone = (string) ($prospect->phone ?? '');
        }

        if ($recipientMember) {
            abort_unless(
                app(FnaClientInviteRecipientService::class)->canInviteRecipient(auth()->user(), null, $recipientMember),
                403,
            );
            $this->authorize('create', FnaClientInvite::class);

            $prefill = app(FnaClientInviteRecipientService::class)->prefillFromMember($recipientMember);
            $this->recipient_name = $prefill['recipient_name'];
            $this->recipient_email = $prefill['recipient_email'];
            $this->recipient_phone = $prefill['recipient_phone'];
        }

        if ($fna) {
            $this->authorize('view', $fna);
            $this->authorize('create', FnaClientInvite::class);
            $this->recipient_name = $this->recipient_name ?: ($fna->client_name ?? '');
            $this->recipient_email = $this->recipient_email ?: (string) ($fna->client_email ?? '');
            $this->recipient_phone = $this->recipient_phone ?: (string) ($fna->client_phone ?? '');
        }
    }

    public function grantCfmAccess(ProspectShareService $shareService): void
    {
        if (! $this->prospect) {
            return;
        }

        $this->authorize('share', $this->prospect);

        $owner = auth()->user();

        abort_unless(
            (int) $this->prospect->owner_id === $owner->id
            && ! app(FnaClientInviteService::class)->agentCanSendInvites($owner),
            403,
        );

        if (! $owner->mentor_id) {
            throw ValidationException::withMessages([
                'cfm' => 'No Certified Field Mentor is assigned to your profile. Ask your sponsor or agency owner to assign a CFM before sharing this prospect.',
            ]);
        }

        $permission = ProspectSharePermission::query()
            ->where('key', 'full_collaboration')
            ->firstOrFail();

        $shareService->applyVisibilityPreset(
            $this->prospect,
            $owner,
            'cfm',
            null,
            $permission->id,
        );

        session()->flash(
            'fna_invite_status',
            'This prospect is now shared with '.$owner->mentor?->name.'. Your CFM can create and send the FNA client portal invite.',
        );

        $this->dispatch('prospect-share-updated');
    }

    public function sendInvite(FnaClientInviteService $invites): void
    {
        $this->createInvite($invites, sendEmail: false);
    }

    public function sendInviteAndEmail(FnaClientInviteService $invites): void
    {
        $this->createInvite($invites, sendEmail: true);
    }

    protected function createInvite(FnaClientInviteService $invites, bool $sendEmail): void
    {
        $this->authorize('create', FnaClientInvite::class);

        $this->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => [$sendEmail ? 'required' : 'nullable', 'email', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:60'],
            'personal_message' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->prospect && blank($this->prospect->email) && blank($this->recipient_email) && $sendEmail) {
            throw ValidationException::withMessages([
                'recipient_email' => 'This prospect has no email on file. Add an email address before sending the invite.',
            ]);
        }

        if ($this->prospect) {
            $this->authorize('view', $this->prospect);
        }

        if ($this->recipientMember) {
            abort_unless(
                app(FnaClientInviteRecipientService::class)->canInviteRecipient(auth()->user(), null, $this->recipientMember),
                403,
            );
        }

        $result = $invites->createInvite(auth()->user(), $this->prospect, [
            'recipient_name' => $this->recipient_name,
            'recipient_email' => $this->recipient_email ?: null,
            'recipient_phone' => $this->recipient_phone ?: null,
            'personal_message' => $this->personal_message,
        ], $this->recipientMember);

        if ($sendEmail) {
            $invites->sendInviteEmail($result['invite'], auth()->user(), $result['security_code']);
        }

        $this->createdSecurityCode = $result['security_code'];
        $this->createdInviteUrl = $result['invite']->inviteUrl();
        $this->reset('personal_message');

        $recipientLabel = $this->recipientMember ? 'member' : 'prospect';

        if ($sendEmail) {
            session()->flash(
                'fna_invite_status',
                'Client portal invite created and emailed to '.$this->recipient_email.'.',
            );
        } else {
            session()->flash(
                'fna_invite_status',
                "Client portal invite created. Share the link and security code with your {$recipientLabel}.",
            );
        }

        $this->dispatch('fna-client-invite-created');
    }

    public function revokeInvite(string $inviteId, FnaClientInviteService $invites): void
    {
        $invite = FnaClientInvite::query()->findOrFail($inviteId);
        $this->authorize('revoke', $invite);

        $invites->revoke($invite, auth()->user());
        session()->flash('fna_invite_status', 'Invite revoked.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $inviteService = app(FnaClientInviteService::class);

        $query = FnaClientInvite::query()
            ->with(['fnaRecord', 'recipientUser'])
            ->latest('created_at');

        if ($this->prospect) {
            $query->where('prospect_id', $this->prospect->id);
        } else {
            $query->where('sender_user_id', $user->id);
        }

        if ($this->recipientMember) {
            $query->where('recipient_user_id', $this->recipientMember->id);
        } elseif ($this->fna) {
            $query->where('fna_record_id', $this->fna->id);
        }

        $cfm = null;
        $cfmShareActive = false;
        $isProspectOwner = false;

        if ($this->prospect) {
            $user->loadMissing('mentor');
            $isProspectOwner = (int) $this->prospect->owner_id === $user->id;
            $cfm = $isProspectOwner ? $user->mentor : null;

            if ($cfm) {
                $cfmShareActive = $this->prospect->visibility_preset === 'cfm'
                    && $this->prospect->shares()
                        ->where('shared_with', $cfm->id)
                        ->where('status', 'active')
                        ->whereNull('revoked_at')
                        ->exists();
            }
        }

        return view('livewire.fna.fna-client-invite-panel', [
            'invites' => $query->limit(20)->get(),
            'canSend' => $user->can('create', FnaClientInvite::class),
            'needsCfmForInvite' => $this->prospect !== null
                && $isProspectOwner
                && ! $inviteService->agentCanSendInvites($user),
            'cfm' => $cfm,
            'cfmShareActive' => $cfmShareActive,
            'recipientContext' => $this->recipientMember
                ? 'member'
                : ($this->prospect ? 'prospect' : 'general'),
            'prospectMissingEmail' => $this->prospect !== null && blank($this->prospect->email),
        ]);
    }
}

