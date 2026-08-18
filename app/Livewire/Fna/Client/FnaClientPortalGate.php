<?php

namespace App\Livewire\Fna\Client;

use App\Models\FnaClientInvite;
use App\Services\Fna\FnaClientInviteService;
use App\Support\FnaClientPortalSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaClientPortalGate extends Component
{
    public string $token;

    public int $step = 1;

    public string $securityCode = '';

    public string $accessEmail = '';

    public string $accessPhone = '';

    public string $accessSsnLastFour = '';

    public ?FnaClientInvite $invite = null;

    public function mount(string $token, FnaClientInviteService $invites): void
    {
        $this->token = $token;
        $this->invite = $invites->findByToken($token);

        abort_unless($this->invite !== null, 404);
        abort_unless($this->invite->isUsable(), 410);

        if (FnaClientPortalSession::isVerifiedFor($this->invite)) {
            $this->redirect(route('fna.client.wizard', $this->token), navigate: true);

            return;
        }

        $this->accessEmail = (string) ($this->invite->recipient_email ?? '');
        $this->accessPhone = (string) ($this->invite->recipient_phone ?? '');

        if ($this->invite->access_credential_hash) {
            $this->step = 1;
        }
    }

    public function verifySecurityCode(FnaClientInviteService $invites): void
    {
        $this->validate([
            'securityCode' => ['required', 'digits:'.config('fna.client_portal.security_code_length', 6)],
        ]);

        if (! $invites->verifySecurityCode($this->invite, $this->securityCode)) {
            $this->addError('securityCode', 'The security code is incorrect.');

            return;
        }

        if ($this->invite->fresh()->access_credential_hash) {
            FnaClientPortalSession::markVerified($this->invite);
            $this->redirect(route('fna.client.wizard', $this->token), navigate: true);

            return;
        }

        $this->step = 2;
        $this->resetErrorBag();
    }

    public function setupAccessCredentials(FnaClientInviteService $invites): void
    {
        $this->validate([
            'accessEmail' => ['required', 'email', 'max:255'],
            'accessPhone' => ['required', 'string', 'max:60'],
            'accessSsnLastFour' => ['required', 'digits:4'],
        ]);

        $invites->setupAccessCredentials(
            $this->invite,
            $this->accessEmail,
            $this->accessPhone,
            $this->accessSsnLastFour,
        );

        FnaClientPortalSession::markVerified($this->invite->fresh());
        $this->redirect(route('fna.client.wizard', $this->token), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.fna.client.fna-client-portal-gate')
            ->layout('fna.client.portal', [
                'title' => 'Secure FNA Access',
                'invite' => $this->invite,
            ]);
    }
}
