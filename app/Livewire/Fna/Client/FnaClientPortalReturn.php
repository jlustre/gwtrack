<?php

namespace App\Livewire\Fna\Client;

use App\Services\Fna\FnaClientInviteService;
use App\Support\FnaClientPortalSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaClientPortalReturn extends Component
{
    public string $accessEmail = '';

    public string $accessPhone = '';

    public string $accessSsnLastFour = '';

    public function login(FnaClientInviteService $invites): void
    {
        $this->validate([
            'accessEmail' => ['required', 'email', 'max:255'],
            'accessPhone' => ['required', 'string', 'max:60'],
            'accessSsnLastFour' => ['required', 'digits:4'],
        ]);

        $invite = $invites->verifyAccessCredentials(
            $this->accessEmail,
            $this->accessPhone,
            $this->accessSsnLastFour,
        );

        if ($invite === null) {
            $this->addError('accessEmail', 'We could not match those credentials to an active FNA invite.');

            return;
        }

        if (! $invite->isUsable()) {
            $this->addError('accessEmail', 'This invite is no longer available.');

            return;
        }

        FnaClientPortalSession::markVerified($invite);
        $this->redirect(route('fna.client.wizard', $invite->token), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.fna.client.fna-client-portal-return')
            ->layout('fna.client.portal', [
                'title' => 'Return to Your FNA',
            ]);
    }
}
