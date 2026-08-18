<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\FnaCalendarBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class FnaCalendarScheduler extends Component
{
    public FnaRecord $fna;

    public string $meetingType = 'fna-client-meeting';

    public string $startsAt = '';

    public int $durationMinutes = 60;

    public string $locationOrLink = '';

    public string $notes = '';

    public string $feedbackMessage = '';

    public string $errorMessage = '';

    public function mount(FnaRecord $fna): void
    {
        $this->authorize('update', $fna);

        $this->fna = $fna->load(['calendarEvent', 'prospect', 'cfm']);

        if ($this->fna->calendarEvent?->starts_at) {
            $this->startsAt = $this->fna->calendarEvent->starts_at->format('Y-m-d\TH:i');
        } else {
            $this->startsAt = now()->addDays(3)->format('Y-m-d\TH:i');
        }

        if ($this->fna->calendarEvent) {
            $this->locationOrLink = $this->fna->calendarEvent->meeting_link
                ?: ($this->fna->calendarEvent->location ?? '');
            $this->durationMinutes = max(15, (int) $this->fna->calendarEvent->starts_at?->diffInMinutes($this->fna->calendarEvent->ends_at));
        }
    }

    public function schedule(FnaCalendarBridge $calendar): void
    {
        $this->authorize('update', $this->fna);

        $this->validate([
            'startsAt' => ['required', 'date'],
            'durationMinutes' => ['required', 'integer', 'min:15', 'max:480'],
            'meetingType' => ['required', 'string'],
            'locationOrLink' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! in_array($this->fna->status, ['approved_by_cfm', 'scheduled_for_client_review', 'follow_up_needed'], true)) {
            $this->errorMessage = 'Client meetings can only be scheduled after CFM approval.';

            return;
        }

        try {
            $event = $calendar->scheduleMeeting($this->fna, auth()->user(), [
                'meeting_type' => $this->meetingType,
                'starts_at' => Carbon::parse($this->startsAt),
                'duration_minutes' => $this->durationMinutes,
                'location_or_link' => $this->locationOrLink ?: null,
                'notes' => $this->notes ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->fna = $this->fna->fresh(['calendarEvent', 'prospect', 'cfm']);
        $this->errorMessage = '';
        $this->feedbackMessage = 'Client FNA review meeting scheduled for '.$event->starts_at?->format('M j, Y g:i A').'.';
        $this->dispatch('fna-review-updated');
        $this->dispatch('prospect-timeline-refresh');
    }

    public function render(): View
    {
        return view('livewire.fna.fna-calendar-scheduler', [
            'meetingTypes' => config('fna.calendar_event_types', []),
            'canSchedule' => in_array($this->fna->status, ['approved_by_cfm', 'scheduled_for_client_review', 'follow_up_needed'], true),
        ]);
    }
}
