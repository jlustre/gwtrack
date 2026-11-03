<?php

namespace App\Livewire\Admin\Cfm;

use App\Models\CfmCertificationRequest;
use App\Services\CfmCertificationWorkflowService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CertificationReviewManager extends Component
{
    use WithPagination;

    /** @var array<int, string> */
    public array $reviewNotes = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage CFM certification'), 403);
    }

    public function approve(int $requestId, CfmCertificationWorkflowService $workflow): void
    {
        $this->validate([
            "reviewNotes.{$requestId}" => ['nullable', 'string', 'max:2000'],
        ]);

        $request = CfmCertificationRequest::query()->findOrFail($requestId);
        $workflow->approve($request, auth()->user(), $this->reviewNotes[$requestId] ?? null);
        unset($this->reviewNotes[$requestId]);
        $this->resetPage();
        session()->flash('cfm-review-status', 'CFM certification approved. Certified Field Mentor access is now active.');
    }

    public function reject(int $requestId, CfmCertificationWorkflowService $workflow): void
    {
        $this->validate([
            "reviewNotes.{$requestId}" => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $request = CfmCertificationRequest::query()->findOrFail($requestId);
        $workflow->reject($request, auth()->user(), $this->reviewNotes[$requestId]);
        unset($this->reviewNotes[$requestId]);
        $this->resetPage();
        session()->flash('cfm-review-status', 'CFM certification request rejected with feedback.');
    }

    public function render(CfmCertificationWorkflowService $workflow): View
    {
        $pendingRequests = $workflow->pendingRequests();
        $pendingRequests->getCollection()->transform(function (CfmCertificationRequest $request) use ($workflow): CfmCertificationRequest {
            $request->setAttribute('eligibility', $workflow->eligibilityFor($request->user));

            return $request;
        });

        return view('livewire.admin.cfm.certification-review-manager', [
            'pendingRequests' => $pendingRequests,
            'recentRequests' => $workflow->recentRequests(),
        ])->layout('layouts.app');
    }
}
