<?php

namespace App\Livewire\Communication;

use App\Services\Communication\AnnouncementAcknowledgementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CommunicationCriticalBanner extends Component
{
    protected $listeners = [
        'announcement-acknowledged' => '$refresh',
    ];

    public function render(AnnouncementAcknowledgementService $acknowledgements): View
    {
        $user = Auth::user();
        $alerts = collect();

        if ($user?->canViewAnnouncements()) {
            $alerts = $acknowledgements->pendingCriticalFor($user)
                ->map(fn ($announcement) => [
                    'slug' => $announcement->slug,
                    'title' => $announcement->title,
                    'summary' => $announcement->summary,
                    'priority' => $announcement->priority,
                    'priority_label' => config("communication-hub.priorities.{$announcement->priority}.label", ucfirst($announcement->priority)),
                    'url' => route('communications.show', $announcement),
                ])
                ->values();
        }

        return view('livewire.communication.communication-critical-banner', [
            'alerts' => $alerts,
        ]);
    }
}
