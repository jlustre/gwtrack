<?php

namespace App\Livewire\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Services\Communication\AnnouncementEventService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnnouncementEventRsvp extends Component
{
    public MessageCenterAnnouncement $announcement;

    public ?string $rsvpStatus = null;

    public function mount(MessageCenterAnnouncement $announcement, AnnouncementEventService $events): void
    {
        $this->announcement = $announcement;

        if ($announcement->calendar_event_id) {
            $this->rsvpStatus = $events->rsvpStatus(auth()->user(), $announcement);
        }
    }

    public function accept(AnnouncementEventService $events): void
    {
        $this->authorize('view', $this->announcement);
        $events->register(auth()->user(), $this->announcement);
        $this->rsvpStatus = 'accepted';
    }

    public function decline(AnnouncementEventService $events): void
    {
        $this->authorize('view', $this->announcement);
        $events->rsvp(auth()->user(), $this->announcement, 'declined');
        $this->rsvpStatus = 'declined';
    }

    public function render(AnnouncementEventService $events): View
    {
        $context = $events->eventContext(auth()->user(), $this->announcement);

        return view('livewire.communication.announcement-event-rsvp', [
            'event' => $context['event'],
            'attendeeCount' => $context['attendee_count'],
        ]);
    }
}
