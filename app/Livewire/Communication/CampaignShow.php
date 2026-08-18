<?php

namespace App\Livewire\Communication;

use App\Models\AnnouncementCampaign;
use App\Services\Communication\CampaignService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Campaign')]
class CampaignShow extends Component
{
    public AnnouncementCampaign $campaign;

    public function mount(AnnouncementCampaign $campaign): void
    {
        abort_unless(auth()->user()?->canViewAnnouncements(), 403);
        $this->campaign = $campaign->load(['creator', 'announcements' => fn ($query) => $query->published()->visible()]);
    }

    public function join(CampaignService $campaigns): void
    {
        $campaigns->joinCampaign($this->campaign, auth()->user());
        session()->flash('communication_status', 'You joined the campaign. Your progress will update automatically.');
    }

    public function render(CampaignService $campaigns): View
    {
        $user = auth()->user();

        return view('livewire.communication.campaign-show', [
            'leaderboard' => $campaigns->leaderboard($this->campaign),
            'participant' => $campaigns->participantFor($user, $this->campaign),
            'typeMeta' => config('communication-hub.campaign_types.'.$this->campaign->type, []),
        ])->layout('layouts.app');
    }
}
