<?php

namespace App\Livewire;

use App\Services\RankAdvancementService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class RankAdvancementTracker extends Component
{
    #[Url]
    public ?int $member = null;

    public ?int $activeProgressId = null;

    public string $memberNotes = '';

    public string $reviewerNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function startRequirement(int $progressId, RankAdvancementService $advancement): void
    {
        $member = $advancement->resolveMember(auth()->user(), $this->member);
        $advancement->startRequirement($member, $progressId);
        session()->flash('rank_advancement_status', 'Requirement marked in progress.');
    }

    public function submitRequirement(int $progressId, RankAdvancementService $advancement): void
    {
        $member = $advancement->resolveMember(auth()->user(), $this->member);
        $advancement->submitRequirement($member, $progressId, $this->memberNotes ?: null);
        $this->resetNotes();
        session()->flash('rank_advancement_status', 'Requirement submitted for leadership review.');
    }

    public function approveRequirement(int $progressId, RankAdvancementService $advancement): void
    {
        $viewer = auth()->user();
        $member = $advancement->resolveMember($viewer, $this->member);
        $advancement->approveRequirement($viewer, $member, $progressId, $this->reviewerNotes ?: null);
        $this->resetNotes();
        session()->flash('rank_advancement_status', 'Requirement approved and marked complete.');
    }

    public function rejectRequirement(int $progressId, RankAdvancementService $advancement): void
    {
        $viewer = auth()->user();
        $member = $advancement->resolveMember($viewer, $this->member);
        $advancement->rejectRequirement($viewer, $member, $progressId, $this->reviewerNotes ?: null);
        $this->resetNotes();
        session()->flash('rank_advancement_status', 'Requirement returned for revision.');
    }

    public function openRequirement(int $progressId): void
    {
        $this->activeProgressId = $progressId;
        $this->resetNotes();
    }

    public function render(RankAdvancementService $advancement): View
    {
        $viewer = auth()->user();
        $member = $advancement->resolveMember($viewer, $this->member);

        return view('livewire.rank-advancement-tracker', [
            'tracker' => $advancement->trackerFor($viewer, $member),
        ]);
    }

    private function resetNotes(): void
    {
        $this->memberNotes = '';
        $this->reviewerNotes = '';
        $this->activeProgressId = null;
    }
}
