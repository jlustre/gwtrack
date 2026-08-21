<?php

namespace App\Livewire;

use App\Models\MemberComplianceRecord;
use App\Services\ComplianceLifecycleService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ComplianceLifecycleHub extends Component
{
    #[Url]
    public ?int $member = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $complianceType = 'eo_insurance';

    public string $title = '';

    public string $jurisdictionKey = '';

    public string $identifier = '';

    public string $effectiveDate = '';

    public string $expirationDate = '';

    public string $creditsRequired = '';

    public string $creditsEarned = '';

    public string $carrierName = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function openCreateForm(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function editRecord(int $recordId): void
    {
        abort_unless($this->canEdit(), 403);

        $member = app(ComplianceLifecycleService::class)->resolveMember(auth()->user(), $this->member);
        $record = MemberComplianceRecord::query()->where('user_id', $member->id)->findOrFail($recordId);

        $this->editingId = $record->id;
        $this->complianceType = $record->compliance_type;
        $this->title = $record->title;
        $this->jurisdictionKey = $record->jurisdiction_key ?? '';
        $this->identifier = $record->identifier ?? '';
        $this->effectiveDate = $record->effective_date?->format('Y-m-d') ?? '';
        $this->expirationDate = $record->expiration_date?->format('Y-m-d') ?? '';
        $this->creditsRequired = $record->credits_required !== null ? (string) $record->credits_required : '';
        $this->creditsEarned = $record->credits_earned !== null ? (string) $record->credits_earned : '';
        $this->carrierName = $record->carrier_name ?? '';
        $this->notes = $record->notes ?? '';
        $this->showForm = true;
    }

    public function saveRecord(ComplianceLifecycleService $compliance): void
    {
        abort_unless($this->canEdit(), 403);

        $validated = $this->validate([
            'complianceType' => ['required', 'in:'.implode(',', array_keys(config('compliance-lifecycle.types', [])))],
            'title' => ['required', 'string', 'max:255'],
            'jurisdictionKey' => ['nullable', 'string', 'max:120'],
            'identifier' => ['nullable', 'string', 'max:120'],
            'effectiveDate' => ['nullable', 'date'],
            'expirationDate' => ['nullable', 'date', 'after_or_equal:effectiveDate'],
            'creditsRequired' => ['nullable', 'numeric', 'min:0'],
            'creditsEarned' => ['nullable', 'numeric', 'min:0'],
            'carrierName' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $member = $compliance->resolveMember(auth()->user(), $this->member);

        $compliance->upsertRecord($member, [
            'compliance_type' => $validated['complianceType'],
            'title' => $validated['title'],
            'jurisdiction_key' => $validated['jurisdictionKey'] ?: null,
            'identifier' => $validated['identifier'] ?: null,
            'effective_date' => $validated['effectiveDate'] ?: null,
            'expiration_date' => $validated['expirationDate'] ?: null,
            'credits_required' => $validated['creditsRequired'] ?: null,
            'credits_earned' => $validated['creditsEarned'] ?: null,
            'carrier_name' => $validated['carrierName'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'status' => 'pending_verification',
        ], $this->editingId);

        $this->resetForm();
        session()->flash('compliance_status', 'Compliance record saved.');
    }

    public function verifyRecord(int $recordId, ComplianceLifecycleService $compliance): void
    {
        $viewer = auth()->user();
        $member = $compliance->resolveMember($viewer, $this->member);
        abort_unless($compliance->canManageMember($viewer, $member), 403);

        $record = MemberComplianceRecord::query()->where('user_id', $member->id)->findOrFail($recordId);
        $compliance->verifyRecord($viewer, $record);

        session()->flash('compliance_status', 'Record verified.');
    }

    public function deleteRecord(int $recordId, ComplianceLifecycleService $compliance): void
    {
        abort_unless($this->canEdit(), 403);

        $member = $compliance->resolveMember(auth()->user(), $this->member);
        $compliance->deleteRecord($member, $recordId);

        session()->flash('compliance_status', 'Compliance record removed.');
    }

    public function syncLicenses(ComplianceLifecycleService $compliance): void
    {
        abort_unless($this->canEdit(), 403);

        $member = $compliance->resolveMember(auth()->user(), $this->member);
        $compliance->syncLicenseRecordsFromProfile($member);

        session()->flash(
            'compliance_status',
            $member->fresh('profile')?->profile?->hasInsuranceLicenses()
                ? 'License numbers and expiration dates were synced from the profile.'
                : 'No life insurance licenses were found on the profile.',
        );
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function render(ComplianceLifecycleService $compliance): View
    {
        $viewer = auth()->user();
        $member = $compliance->resolveMember($viewer, $this->member);

        return view('livewire.compliance-lifecycle-hub', [
            'hub' => $compliance->hubFor($viewer, $member),
            'typeConfig' => config('compliance-lifecycle.types.'.$this->complianceType, []),
        ])->layout('layouts.app');
    }

    private function canEdit(): bool
    {
        $viewer = auth()->user();
        $service = app(ComplianceLifecycleService::class);
        $member = $service->resolveMember($viewer, $this->member);

        return $viewer->id === $member->id || $service->canManageMember($viewer, $member);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->complianceType = 'eo_insurance';
        $this->title = '';
        $this->jurisdictionKey = '';
        $this->identifier = '';
        $this->effectiveDate = '';
        $this->expirationDate = '';
        $this->creditsRequired = '';
        $this->creditsEarned = '';
        $this->carrierName = '';
        $this->notes = '';
        $this->resetValidation();
    }
}
