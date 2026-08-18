<?php

namespace App\Livewire\Communication;

use App\Services\Communication\CampaignService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Campaign Center')]
class CampaignCenter extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->canViewAnnouncements(), 403);
    }

    public function render(CampaignService $campaigns): View
    {
        $user = auth()->user();

        return view('livewire.communication.campaign-center', [
            'campaigns' => $campaigns->activeCampaignsFor($user),
            'canManage' => $user->can('manage campaigns'),
        ])->layout('layouts.app');
    }
}
