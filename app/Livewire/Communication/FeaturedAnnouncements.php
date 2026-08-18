<?php

namespace App\Livewire\Communication;

use App\Services\Communication\AnnouncementEngagementService;
use App\Services\Communication\CommunicationHubService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FeaturedAnnouncements extends Component
{
    public function render(
        CommunicationHubService $hub,
        AnnouncementEngagementService $engagement,
    ): View {
        $user = auth()->user();

        if (! $user?->canViewAnnouncements()) {
            return view('livewire.communication.featured-announcements', [
                'featured' => collect(),
                'unreadCount' => 0,
            ]);
        }

        $snapshot = $hub->dashboardCommunicationsFor($user);

        return view('livewire.communication.featured-announcements', [
            'featured' => collect($snapshot['featured']),
            'unreadCount' => $snapshot['unread_count'],
        ]);
    }
}
