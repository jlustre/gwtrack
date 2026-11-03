<?php

namespace App\Livewire\Cfm;

use App\Models\CfmCertificationRequest;
use App\Services\CfmCertificationWorkflowService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificationApplication extends Component
{
    public string $requestNotes = '';

    public function submit(CfmCertificationWorkflowService $workflow): void
    {
        $validated = $this->validate([
            'requestNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workflow->submit(auth()->user(), $validated['requestNotes'] ?: null);
        $this->requestNotes = '';
        session()->flash('cfm-certification-status', 'Your CFM certification request has been submitted for review.');
    }

    public function render(CfmCertificationWorkflowService $workflow): View
    {
        $user = auth()->user();

        return view('livewire.cfm.certification-application', [
            'eligibility' => $workflow->eligibilityFor($user),
            'latestRequest' => CfmCertificationRequest::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first(),
        ]);
    }
}
