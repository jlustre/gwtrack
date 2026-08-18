<?php

namespace App\Livewire\Training;

use App\Models\UserTrainingCertification;
use App\Services\Training\TrainingCertificationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificationReviews extends Component
{
    public function approve(int $certificationId, TrainingCertificationService $certifications): void
    {
        $record = UserTrainingCertification::query()->findOrFail($certificationId);
        $certifications->approve($record, auth()->user());
        session()->flash('review_status', 'approved');
    }

    public function reject(int $certificationId, TrainingCertificationService $certifications): void
    {
        $record = UserTrainingCertification::query()->findOrFail($certificationId);
        $certifications->reject($record, auth()->user());
        session()->flash('review_status', 'rejected');
    }

    public function render(TrainingCertificationService $certifications): View
    {
        return view('livewire.training.certification-reviews', [
            'pending' => $certifications->pendingReviewsFor(auth()->user()),
        ]);
    }
}
