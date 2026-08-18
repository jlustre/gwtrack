<?php

namespace App\Livewire\Fna;

use App\Models\Prospect;
use App\Models\User;
use App\Services\Fna\FnaClientInviteRecipientService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class FnaClientInviteModal extends Component
{
    public bool $show = false;

    public ?Prospect $prospect = null;

    public ?User $recipientMember = null;

    #[On('open-fna-client-invite-modal')]
    public function openForProspect(string $prospectId): void
    {
        $this->prospect = Prospect::query()->findOrFail($prospectId);
        $this->recipientMember = null;
        $this->authorize('requestFnaClientPortal', $this->prospect);

        $this->show = true;
    }

    #[On('open-fna-client-invite-member-modal')]
    public function openForMember(int $memberUserId): void
    {
        $this->recipientMember = User::query()->findOrFail($memberUserId);
        $this->prospect = null;

        abort_unless(
            app(FnaClientInviteRecipientService::class)->canInviteRecipient(auth()->user(), null, $this->recipientMember),
            403,
        );
        abort_unless(auth()->user()?->can('create', \App\Models\FnaClientInvite::class), 403);

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->prospect = null;
        $this->recipientMember = null;
    }

    public function render(): View
    {
        return view('livewire.fna.fna-client-invite-modal');
    }
}

