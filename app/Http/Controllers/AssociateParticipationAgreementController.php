<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssociateParticipationAgreementRequest;
use App\Models\AssociateParticipationAgreement;
use App\Services\AssociateParticipationAgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssociateParticipationAgreementController extends Controller
{
    public function __construct(
        private readonly AssociateParticipationAgreementService $service,
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $agreement = AssociateParticipationAgreement::query()
            ->where('user_id', $user->id)
            ->first();

        return view('resources.forms.associate-participation-agreement', [
            'formData' => $this->service->formDataForUser($user),
            'agreement' => $agreement,
            'isSubmitted' => $agreement?->isSubmitted() ?? false,
        ]);
    }

    public function store(StoreAssociateParticipationAgreementRequest $request): RedirectResponse
    {
        $this->service->submit($request->user(), $request->validated());

        return redirect()
            ->route('resources.forms.associate-participation-agreement')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => 'Your Associate Participation Agreement has been submitted successfully.',
            ]);
    }

    public function downloadPdf(Request $request): StreamedResponse|RedirectResponse
    {
        $agreement = AssociateParticipationAgreement::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $agreement?->isSubmitted() || blank($agreement->pdf_path)) {
            return redirect()
                ->route('resources.forms.associate-participation-agreement')
                ->with('profile_feedback', [
                    'type' => 'error',
                    'message' => 'No signed agreement PDF is available yet.',
                ]);
        }

        if (! Storage::disk('public')->exists($agreement->pdf_path)) {
            $this->service->generatePdf($agreement);
            $agreement->refresh();
        }

        return Storage::disk('public')->download(
            $agreement->pdf_path,
            'associate-participation-agreement.pdf',
        );
    }
}
