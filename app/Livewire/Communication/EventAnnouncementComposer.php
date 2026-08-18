<?php

namespace App\Livewire\Communication;

use App\Services\Communication\AnnouncementEventService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Event Announcement')]
class EventAnnouncementComposer extends Component
{
    public string $title = '';

    public string $summary = '';

    public string $body = '';

    public string $starts_at = '';

    public string $location = '';

    public string $meeting_link = '';

    public bool $publish_now = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('create', \App\Models\MessageCenterAnnouncement::class), 403);
        $this->starts_at = now()->addDays(7)->format('Y-m-d\TH:i');
    }

    public function save(AnnouncementEventService $events): void
    {
        $this->authorize('create', \App\Models\MessageCenterAnnouncement::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
        ]);

        $announcement = $events->publishEventAnnouncement([
            ...$validated,
            'priority' => 'important',
            'is_featured' => true,
        ], auth()->user());

        session()->flash('communication_status', 'Event announcement published and calendar event created.');

        $this->redirectRoute('communications.show', $announcement, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.communication.event-announcement-composer')->layout('layouts.app');
    }
}
