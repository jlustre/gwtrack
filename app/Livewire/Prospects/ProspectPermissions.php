<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectAccessLog;
use App\Models\ProspectShare;
use App\Models\ProspectSharePermission;
use App\Services\Prospects\CrmGovernanceService;
use App\Services\Prospects\ProspectShareService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProspectPermissions extends Component
{
    use WithPagination;

    #[Url(as: 'share_status')]
    public string $shareStatusFilter = 'active';

    public ?string $bulkProspectId = null;

    public string $bulkPreset = 'private';

    public ?int $bulkPermissionId = null;

    public ?string $bulkExpiresAt = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function updatedShareStatusFilter(): void
    {
        $this->resetPage('sharesPage');
    }

    public function revokeShare(int $shareId, ProspectShareService $shareService): void
    {
        $share = ProspectShare::query()->with('prospect')->findOrFail($shareId);
        $this->authorize('revoke', $share);

        $shareService->revokeShare($share, auth()->user());

        session()->flash('status', 'Share access revoked.');
    }

    public function applyBulkPreset(ProspectShareService $shareService): void
    {
        $this->validate([
            'bulkProspectId' => ['required', 'exists:prospects,id'],
            'bulkPreset' => ['required', 'string'],
            'bulkPermissionId' => ['nullable', 'exists:prospect_share_permissions,id'],
            'bulkExpiresAt' => ['nullable', 'date'],
        ]);

        $prospect = Prospect::query()->findOrFail($this->bulkProspectId);
        $this->authorize('share', $prospect);

        $expiresAt = $this->bulkExpiresAt ? now()->parse($this->bulkExpiresAt) : null;

        $shareService->applyVisibilityPreset(
            $prospect,
            auth()->user(),
            $this->bulkPreset,
            null,
            $this->bulkPermissionId,
            $expiresAt,
        );

        session()->flash('status', 'Visibility preset applied.');

        $this->reset(['bulkProspectId', 'bulkPreset', 'bulkPermissionId', 'bulkExpiresAt']);
        $this->bulkPreset = 'private';
    }

    public function render(CrmGovernanceService $governance): View
    {
        $user = auth()->user();

        $shares = ProspectShare::query()
            ->with(['prospect:id,first_name,last_name,preferred_name', 'sharedWith:id,name', 'permission:id,name,key'])
            ->where('granted_by', $user->id)
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
            ->paginate(10, ['*'], 'sharesPage');

        $ownedProspectIds = Prospect::query()->where('owner_id', $user->id)->pluck('id');
        $grantedProspectIds = ProspectShare::query()->where('granted_by', $user->id)->pluck('prospect_id');

        $accessLogs = ProspectAccessLog::query()
            ->with(['prospect:id,first_name,last_name', 'actor:id,name'])
            ->where(function ($query) use ($user, $ownedProspectIds, $grantedProspectIds): void {
                $query->where('actor_id', $user->id)
                    ->orWhere('subject_user_id', $user->id)
                    ->orWhereIn('prospect_id', $ownedProspectIds)
                    ->orWhereIn('prospect_id', $grantedProspectIds);
            })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'logsPage');

        return view('livewire.prospects.prospect-permissions', [
            'shares' => $shares,
            'accessLogs' => $accessLogs,
            'ownedProspects' => Prospect::query()
                ->where('owner_id', $user->id)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'preferred_name', 'visibility_preset']),
            'permissions' => ProspectSharePermission::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'key']),
            'visibilityPresets' => config('prospects.visibility_presets', []),
            'governanceReport' => $governance->reportFor($user),
        ]);
    }
}
