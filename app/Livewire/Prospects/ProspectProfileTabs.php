<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectNote;
use App\Services\Prospects\ProspectFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectProfileTabs extends Component
{
    public Prospect $prospect;

    public string $activeTab = 'timeline';

    public string $noteBody = '';

    public bool $noteIsPrivate = false;

    public function mount(Prospect $prospect, string $initialTab = 'timeline'): void
    {
        $this->prospect = $prospect;
        $this->authorize('view', $prospect);

        $tab = request()->query('tab', $initialTab);
        if (in_array($tab, ['timeline', 'activities', 'communications', 'calendar', 'notes', 'fna'], true)) {
            $this->activeTab = $tab;
        }
    }

    #[On('prospect-timeline-refresh')]
    public function refreshContent(): void
    {
        $this->prospect->refresh();
    }

    public function addNote(ProspectFunnelService $funnels): void
    {
        $this->authorize('create', [ProspectNote::class, $this->prospect]);

        $this->validate([
            'noteBody' => ['required', 'string', 'max:5000'],
        ]);

        $funnels->addNote($this->prospect, auth()->user(), $this->noteBody, $this->noteIsPrivate);

        $this->reset('noteBody');
        $this->noteIsPrivate = false;
        $this->activeTab = 'notes';

        $this->dispatch('prospect-timeline-refresh');
        session()->flash('tab_status', 'Note added.');
    }

    public function openLogCall(): void
    {
        $this->authorize('update', $this->prospect);

        $this->dispatch(
            'open-prospect-quick-log-modal',
            prospectId: $this->prospect->id,
            tab: 'activity',
            activityType: 'phone_call',
        );
    }

    public function openLogActivity(): void
    {
        $this->authorize('update', $this->prospect);

        $this->dispatch(
            'open-prospect-quick-log-modal',
            prospectId: $this->prospect->id,
            tab: 'activity',
        );
    }

    public function openLogCommunication(): void
    {
        $this->authorize('update', $this->prospect);

        $this->dispatch(
            'open-prospect-quick-log-modal',
            prospectId: $this->prospect->id,
            tab: 'communication',
        );
    }

    public function render(): View
    {
        $this->prospect->load(['notes.user', 'activities.user', 'stage', 'funnel', 'source']);

        return view('livewire.prospects.prospect-profile-tabs', [
            'notes' => $this->prospect->notes()->with('user')->latest()->limit(50)->get(),
            'activities' => $this->prospect->activities()->with('user')->latest('occurred_at')->limit(50)->get(),
            'communications' => $this->prospect->communications()->with(['user', 'type'])->latest('contacted_at')->limit(50)->get(),
            'calendarEvents' => $this->prospect->calendarEvents()->with(['type', 'category'])->orderByDesc('starts_at')->limit(20)->get(),
            'activityTypes' => config('prospects.activity_types'),
            'canAddNotes' => auth()->user()->can('create', [ProspectNote::class, $this->prospect]),
            'canLogActivities' => auth()->user()->can('update', $this->prospect),
        ]);
    }
}
