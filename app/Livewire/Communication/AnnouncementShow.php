<?php

namespace App\Livewire\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Services\Communication\CommunicationHubService;
use App\Services\Communication\RecognitionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

class AnnouncementShow extends Component
{
    public MessageCenterAnnouncement $announcement;

    public bool $isBookmarked = false;

    public function mount(MessageCenterAnnouncement $announcement, CommunicationHubService $hub): void
    {
        $this->authorize('view', $announcement);

        $this->announcement = $announcement->load(['category', 'creator', 'attachments', 'campaign', 'calendarEvent']);

        if ($announcement->isPublished()) {
            $hub->markRead(auth()->user(), $announcement, openedFull: true);
        }

        $this->isBookmarked = $hub->isBookmarked(auth()->user(), $announcement);
    }

    public function acknowledge(CommunicationHubService $hub): void
    {
        $this->authorize('view', $this->announcement);

        $hub->acknowledge(auth()->user(), $this->announcement);

        $this->dispatch('announcement-acknowledged');

        session()->flash('communication_status', 'Thank you — your acknowledgement has been recorded.');
    }

    public function toggleBookmark(CommunicationHubService $hub): void
    {
        $this->authorize('view', $this->announcement);

        $this->isBookmarked = $hub->toggleBookmark(auth()->user(), $this->announcement);
    }

    public function render(CommunicationHubService $hub, RecognitionService $recognition): View
    {
        $recognitionContext = $this->announcement->category?->code === 'recognition'
            ? $recognition->recognitionContext($this->announcement)
            : null;

        return view('livewire.communication.announcement-show', [
            'priorities' => config('communication-hub.priorities', []),
            'hasAcknowledged' => $hub->hasAcknowledged(auth()->user(), $this->announcement),
            'recognitionContext' => $recognitionContext,
        ])->layout('layouts.app');
    }
}
