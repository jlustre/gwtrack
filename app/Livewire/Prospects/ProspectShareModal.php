<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectSharePermission;
use App\Models\User;
use App\Services\Prospects\ProspectShareService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectShareModal extends Component
{
    public bool $show = false;

    public ?string $prospectId = null;

    public string $preset = 'private';

    public ?int $permissionId = null;

    public ?string $expiresAt = null;

    public ?int $explicitUserId = null;

    public string $userSearch = '';

    public function mount(): void
    {
        $defaultPermission = ProspectSharePermission::query()->where('key', 'view_only')->first();
        $this->permissionId = $defaultPermission?->id;
    }

    #[On('open-prospect-share-modal')]
    public function open(string $prospectId): void
    {
        $prospect = Prospect::query()->findOrFail($prospectId);
        $this->authorize('share', $prospect);

        $this->prospectId = $prospectId;
        $this->preset = $prospect->visibility_preset ?? 'private';
        $this->expiresAt = null;
        $this->explicitUserId = null;
        $this->userSearch = '';
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function save(ProspectShareService $shareService): void
    {
        $this->validate([
            'preset' => ['required', 'string'],
            'permissionId' => ['nullable', 'exists:prospect_share_permissions,id'],
            'expiresAt' => ['nullable', 'date'],
            'explicitUserId' => ['required_if:preset,user', 'nullable', 'exists:users,id'],
        ]);

        $prospect = Prospect::query()->findOrFail($this->prospectId);
        $this->authorize('share', $prospect);

        $expiresAt = $this->expiresAt ? now()->parse($this->expiresAt) : null;

        $shareService->applyVisibilityPreset(
            $prospect,
            auth()->user(),
            $this->preset,
            $this->explicitUserId,
            $this->permissionId,
            $expiresAt,
        );

        $this->show = false;
        session()->flash('status', 'Sharing settings updated.');

        $this->dispatch('prospect-share-updated');
    }

    public function render(): View
    {
        $userResults = collect();

        if ($this->preset === 'user' && strlen($this->userSearch) >= 2) {
            $userResults = User::query()
                ->where('id', '!=', auth()->id())
                ->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('email', 'like', '%'.$this->userSearch.'%');
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'email']);
        }

        return view('livewire.prospects.prospect-share-modal', [
            'permissions' => ProspectSharePermission::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'key']),
            'visibilityPresets' => config('prospects.visibility_presets', []),
            'userResults' => $userResults,
            'selectedUser' => $this->explicitUserId
                ? User::query()->find($this->explicitUserId, ['id', 'name', 'email'])
                : null,
        ]);
    }
}
