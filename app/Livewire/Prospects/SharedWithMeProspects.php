<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectShare;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SharedWithMeProspects extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function render(): View
    {
        $shares = ProspectShare::query()
            ->with([
                'prospect:id,first_name,last_name,preferred_name,interest_level,pipeline_stage_id',
                'prospect.stage:id,name',
                'grantedBy:id,name',
                'permission:id,name,key',
            ])
            ->where('shared_with', auth()->id())
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('granted_at')
            ->paginate(15);

        return view('livewire.prospects.shared-with-me-prospects', [
            'shares' => $shares,
            'embedded' => $this->embedded,
        ]);
    }
}
