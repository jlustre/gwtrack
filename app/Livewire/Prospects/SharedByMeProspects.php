<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectShare;
use App\Services\Prospects\ProspectShareService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SharedByMeProspects extends Component
{
    use WithPagination;

    public bool $embedded = false;

    #[Url(as: 'share_status')]
    public string $shareStatusFilter = 'active';

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function updatedShareStatusFilter(): void
    {
        $this->resetPage();
    }

    public function revokeShare(int $shareId, ProspectShareService $shareService): void
    {
        $share = ProspectShare::query()->with('prospect')->findOrFail($shareId);
        $this->authorize('revoke', $share);

        $shareService->revokeShare($share, auth()->user());

        session()->flash('status', 'Share access revoked.');
    }

    public function render(): View
    {
        $shares = ProspectShare::query()
            ->with([
                'prospect:id,first_name,last_name,preferred_name,visibility_preset',
                'sharedWith:id,name',
                'permission:id,name,key',
            ])
            ->where('granted_by', auth()->id())
            ->when($this->shareStatusFilter === 'active', function ($query): void {
                $query->where('status', 'active')
                    ->whereNull('revoked_at')
                    ->where(function ($query): void {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->when($this->shareStatusFilter === 'inactive', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'revoked')
                        ->orWhereNotNull('revoked_at')
                        ->orWhere('expires_at', '<=', now());
                });
            })
            ->orderByDesc('granted_at')
            ->paginate(15);

        return view('livewire.prospects.shared-by-me-prospects', [
            'shares' => $shares,
            'embedded' => $this->embedded,
        ]);
    }
}
