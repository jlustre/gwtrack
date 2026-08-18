<?php

namespace App\Http\Controllers;

use App\Models\MentorAssignment;
use App\Models\UserTrainingCertification;
use App\Services\Training\TrainingCertificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingCertificationController extends Controller
{
    public function __construct(private readonly TrainingCertificationService $certifications) {}

    public function index(Request $request): View
    {
        return view('training.certifications.index', [
            'rows' => $this->certifications->certificationRowsFor($request->user()),
        ]);
    }

    public function show(Request $request, UserTrainingCertification $userCertification): View
    {
        abort_unless((int) $userCertification->user_id === (int) $request->user()->id, 403);

        $userCertification->load(['certification.module', 'approvedBy']);

        return view('training.certifications.show', [
            'record' => $userCertification,
        ]);
    }

    public function reviews(Request $request): View
    {
        $user = $request->user();
        $canReview = $user->can('manage training')
            || MentorAssignment::query()
                ->where('mentor_id', $user->id)
                ->where('status', 'active')
                ->exists();

        abort_unless($canReview, 403);

        return view('training.certifications.reviews', [
            'pending' => $this->certifications->pendingReviewsFor($user),
        ]);
    }
}
