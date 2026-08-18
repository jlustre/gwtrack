<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\FnaAiAssistantService;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class FnaSubmitForReviewModal extends Component
{
    public bool $show = false;

    public ?string $fnaId = null;

    public ?string $clientName = null;

    public ?string $referenceCode = null;

    public int $completenessScore = 0;

    public array $missingSections = [];

    public array $completenessSuggestions = [];

    public bool $aiEnabled = false;

    public string $complianceNotice = '';

    public ?string $cfmName = null;

    public string $errorMessage = '';

    #[On('open-fna-submit-modal')]
    public function open(string $fnaId): void
    {
        $fna = FnaRecord::with('owner')->findOrFail($fnaId);
        $this->authorize('submit', $fna);

        $completeness = app(FnaCompletenessService::class);
        $ai = app(FnaAiAssistantService::class);
        $cfm = app(FnaRecordService::class)->resolveCfmForOwner($fna->owner);

        $this->fnaId = $fna->id;
        $this->clientName = $fna->client_name;
        $this->referenceCode = $fna->reference_code;
        $this->completenessScore = $completeness->score($fna);
        $this->missingSections = $completeness->missingSections($fna);
        $this->aiEnabled = $ai->isEnabled('completeness_checker');
        $this->completenessSuggestions = $this->aiEnabled ? $ai->completenessSuggestions($fna) : [];
        $this->complianceNotice = $this->aiEnabled ? $ai->complianceNotice() : '';
        $this->cfmName = $cfm?->name;
        $this->errorMessage = '';
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function submit(FnaReviewService $reviews): void
    {
        if (! $this->fnaId) {
            return;
        }

        $fna = FnaRecord::findOrFail($this->fnaId);
        $this->authorize('submit', $fna);

        try {
            $reviews->submitForReview($fna, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->show = false;
        session()->flash('fna_status', 'FNA submitted to your CFM for review.');

        $this->redirect(route('team.fna.show', $fna));
    }

    public function render(): View
    {
        return view('livewire.fna.fna-submit-for-review-modal');
    }
}
