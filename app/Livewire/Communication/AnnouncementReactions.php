<?php

namespace App\Livewire\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Services\Communication\AnnouncementEngagementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnnouncementReactions extends Component
{
    public MessageCenterAnnouncement $announcement;

    /** @var array<string, int> */
    public array $counts = [];

    public ?string $userReaction = null;

    public function mount(MessageCenterAnnouncement $announcement, AnnouncementEngagementService $engagement): void
    {
        $this->announcement = $announcement;
        $this->refreshCounts($engagement);
    }

    public function react(string $reaction, AnnouncementEngagementService $engagement): void
    {
        $this->authorize('view', $this->announcement);

        $this->userReaction = $engagement->toggleReaction(auth()->user(), $this->announcement, $reaction);
        $this->refreshCounts($engagement);
    }

    public function render(): View
    {
        return view('livewire.communication.announcement-reactions', [
            'reactions' => config('communication-hub.reactions', []),
        ]);
    }

    private function refreshCounts(AnnouncementEngagementService $engagement): void
    {
        $this->counts = $engagement->reactionCountsFor($this->announcement);
        $this->userReaction = $engagement->userReaction(auth()->user(), $this->announcement);
    }
}
