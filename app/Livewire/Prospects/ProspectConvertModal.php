<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectConversionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectConvertModal extends Component
{
    public bool $show = false;

    public ?string $prospectId = null;

    public string $tab = 'associate';

    public string $associateNotes = '';

    public string $clientPolicyReference = '';

    public string $clientApplicationReference = '';

    public string $clientNotes = '';

    public string $inactiveReason = '';

    public ?string $invitationUrl = null;

    public ?string $statusMessage = null;

    #[On('open-prospect-convert-modal')]
    public function open(string $prospectId, string $tab = 'associate'): void
    {
        $prospect = Prospect::query()->findOrFail($prospectId);
        $this->authorize('convert', $prospect);

        $this->prospectId = $prospectId;
        $this->tab = in_array($tab, ['associate', 'client', 'inactive'], true) ? $tab : 'associate';
        $this->resetFormState();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetFormState();
    }

    public function convertAssociate(ProspectConversionService $conversionService): void
    {
        $prospect = $this->prospect();
        $this->authorize('convert', $prospect);

        $this->validate([
            'associateNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $result = $conversionService->convertToAssociate(
            $prospect,
            auth()->user(),
            $this->associateNotes !== '' ? $this->associateNotes : null,
        );

        $this->invitationUrl = $result['invitation_url'];
        $this->statusMessage = 'Registration invitation created. Share the link below with your prospect.';
        $this->dispatch('prospect-conversion-updated');
    }

    public function convertClient(ProspectConversionService $conversionService): void
    {
        $prospect = $this->prospect();
        $this->authorize('convert', $prospect);

        $this->validate([
            'clientPolicyReference' => ['required', 'string', 'max:255'],
            'clientApplicationReference' => ['nullable', 'string', 'max:255'],
            'clientNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $conversionService->convertToClient(
            $prospect,
            auth()->user(),
            $this->clientPolicyReference,
            $this->clientApplicationReference !== '' ? $this->clientApplicationReference : null,
            $this->clientNotes !== '' ? $this->clientNotes : null,
        );

        $this->show = false;
        $this->resetFormState();
        session()->flash('status', 'Prospect converted to client.');
        $this->dispatch('prospect-conversion-updated');
    }

    public function convertInactive(ProspectConversionService $conversionService): void
    {
        $prospect = $this->prospect();
        $this->authorize('convert', $prospect);

        $this->validate([
            'inactiveReason' => ['nullable', 'string', 'max:5000'],
        ]);

        $conversionService->convertToInactive(
            $prospect,
            auth()->user(),
            $this->inactiveReason !== '' ? $this->inactiveReason : null,
        );

        $this->show = false;
        $this->resetFormState();
        session()->flash('status', 'Prospect marked inactive.');
        $this->dispatch('prospect-conversion-updated');
    }

    public function render(): View
    {
        $prospect = $this->prospectId
            ? Prospect::query()->find($this->prospectId)
            : null;

        return view('livewire.prospects.prospect-convert-modal', [
            'prospect' => $prospect,
        ]);
    }

    private function prospect(): Prospect
    {
        return Prospect::query()->findOrFail($this->prospectId);
    }

    private function resetFormState(): void
    {
        $this->associateNotes = '';
        $this->clientPolicyReference = '';
        $this->clientApplicationReference = '';
        $this->clientNotes = '';
        $this->inactiveReason = '';
        $this->invitationUrl = null;
        $this->statusMessage = null;
    }
}
