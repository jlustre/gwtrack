<?php

namespace App\Livewire\Admin\Communication;

use App\Services\Communication\AnnouncementAnalyticsService;
use App\Services\Communication\BroadcastService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminCommunicationDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view communication analytics'), 403);
    }

    public function render(
        AnnouncementAnalyticsService $analytics,
        BroadcastService $broadcasts,
    ): View {
        return view('livewire.admin.communication.admin-communication-dashboard', [
            'metrics' => $analytics->dashboardMetrics(),
            'topAnnouncements' => $analytics->topAnnouncements(),
            'trend' => $analytics->trend(),
            'recentBroadcasts' => $broadcasts->recent(5),
        ]);
    }
}
