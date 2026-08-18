<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaReviewPanel extends Component
{
    public FnaRecord $fna;

    public string $comment = '';

    public string $feedbackMessage = '';

    public string $errorMessage = '';

    public function mount(FnaRecord $fna, FnaReviewService $reviews): void
    {
        $this->authorize('view', $fna);

        $this->fna = $fna->load([
            'owner:id,name',
            'household',
            'incomeDetail',
            'debtDetail',
            'dimeAnalysis',
            'reviewComments.user:id,name',
        ]);

        if (auth()->user()?->can('review', $this->fna) && $this->fna->status === 'submitted_to_cfm') {
            $this->fna = $reviews->beginReview($this->fna, auth()->user());
        }
    }

    public function approve(FnaReviewService $reviews): void
    {
        $this->authorize('review', $this->fna);

        try {
            $this->fna = $reviews->approve(
                $this->fna,
                auth()->user(),
                $this->comment ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->comment = '';
        $this->errorMessage = '';
        $this->feedbackMessage = 'FNA approved. The associate has been notified.';
        $this->dispatch('fna-review-updated');
    }

    public function requestRevision(FnaReviewService $reviews): void
    {
        $this->authorize('review', $this->fna);

        $this->validate(['comment' => 'required|string|min:10']);

        try {
            $this->fna = $reviews->requestRevision($this->fna, auth()->user(), $this->comment);
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->comment = '';
        $this->errorMessage = '';
        $this->feedbackMessage = 'Revision requested. The associate has been notified.';
        $this->dispatch('fna-review-updated');
    }

    public function render(): View
    {
        $missing = app(FnaCompletenessService::class)->missingSections($this->fna);

        return view('livewire.fna.fna-review-panel', compact('missing'));
    }
}
