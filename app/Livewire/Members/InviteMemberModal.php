<?php

namespace App\Livewire\Members;

use App\Models\RegistrationInvitation;
use App\Services\Members\MemberInvitationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class InviteMemberModal extends Component
{
    public bool $show = false;

    public string $email = '';

    public string $recipientEmail = '';

    public ?int $invitationId = null;

    public ?string $invitationUrl = null;

    public ?string $invitationCode = null;

    public ?string $expiresAtLabel = null;

    public ?string $emailSentMessage = null;

    public bool $loadedExistingInvitation = false;

    #[On('open-invite-member-modal')]
    public function open(?int $invitationId = null): void
    {
        abort_unless($this->canInvite(), 403);

        $this->resetForm();

        if ($invitationId !== null && ! $this->loadExistingInvitation($invitationId)) {
            session()->flash('invite_member_status', 'That invitation is no longer available. It may have already been used, deleted, or expired.');
        }

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function generate(MemberInvitationService $invitations): void
    {
        abort_unless($this->canInvite(), 403);

        $validated = $this->validate([
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $invitation = $invitations->createInvitation(
            auth()->user(),
            $validated['email'] ?? null,
        );

        $this->invitationId = $invitation->id;
        $this->recipientEmail = $validated['email'] ?? '';
        $this->invitationUrl = $invitation->invitationUrl();
        $this->invitationCode = $invitation->code;
        $this->expiresAtLabel = $invitation->expires_at?->format('M j, Y g:i A');
        $this->emailSentMessage = null;
    }

    public function sendEmail(MemberInvitationService $invitations): void
    {
        abort_unless($this->canInvite(), 403);
        abort_unless($this->invitationId, 403);

        $validated = $this->validate([
            'recipientEmail' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ], [
            'recipientEmail.required' => 'Enter the recipient email address before sending the invitation link.',
        ]);

        $invitation = RegistrationInvitation::query()->findOrFail($this->invitationId);

        abort_unless($invitation->sponsor_id === auth()->id(), 403);

        $invitations->sendInvitationEmail(
            auth()->user(),
            $invitation,
            $validated['recipientEmail'],
        );

        $this->emailSentMessage = 'Invitation email sent to '.$validated['recipientEmail'].'.';
        $this->resetValidation('recipientEmail');
        $this->dispatch('invitation-email-sent');
        $this->dispatch('my-tasks-changed');

        if ($this->loadedExistingInvitation) {
            $this->close();
        }
    }

    public function deleteInvitation(MemberInvitationService $invitations): void
    {
        abort_unless($this->canInvite(), 403);
        abort_unless($this->invitationId, 403);

        $invitation = RegistrationInvitation::query()->findOrFail($this->invitationId);

        abort_unless($invitation->sponsor_id === auth()->id(), 403);
        abort_if($invitation->last_emailed_at !== null, 403, 'This invitation has already been emailed.');

        $invitations->revokeInvitation(auth()->user(), $invitation);

        $this->dispatch('invitation-deleted');
        $this->dispatch('my-tasks-changed');
        $this->close();
    }

    public function startAnother(): void
    {
        $this->invitationId = null;
        $this->invitationUrl = null;
        $this->invitationCode = null;
        $this->expiresAtLabel = null;
        $this->recipientEmail = '';
        $this->emailSentMessage = null;
        $this->email = '';
        $this->loadedExistingInvitation = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.members.invite-member-modal', [
            'canInvite' => $this->canInvite(),
        ]);
    }

    private function canInvite(): bool
    {
        $user = auth()->user();

        return $user
            ? app(MemberInvitationService::class)->canCreateInvitation($user)
            : false;
    }

    private function resetForm(): void
    {
        $this->email = '';
        $this->recipientEmail = '';
        $this->invitationId = null;
        $this->invitationUrl = null;
        $this->invitationCode = null;
        $this->expiresAtLabel = null;
        $this->emailSentMessage = null;
        $this->loadedExistingInvitation = false;
        $this->resetValidation();
    }

    private function loadExistingInvitation(int $invitationId): bool
    {
        $invitation = RegistrationInvitation::query()
            ->whereKey($invitationId)
            ->where('sponsor_id', auth()->id())
            ->whereNull('last_emailed_at')
            ->whereNull('accepted_at')
            ->whereNull('accepted_by')
            ->whereNull('revoked_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $invitation || ! $invitation->isAvailable() || $invitation->hasCompletedRegistration()) {
            return false;
        }

        $this->invitationId = $invitation->id;
        $this->invitationUrl = $invitation->invitationUrl();
        $this->invitationCode = $invitation->code;
        $this->recipientEmail = $invitation->email ?? '';
        $this->email = $invitation->email ?? '';
        $this->expiresAtLabel = $invitation->expires_at?->format('M j, Y g:i A');
        $this->loadedExistingInvitation = true;

        return true;
    }
}
