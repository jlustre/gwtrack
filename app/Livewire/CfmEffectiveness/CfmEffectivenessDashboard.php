<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class CfmEffectivenessDashboard extends Component
{
    #[Url]
    public ?int $cfmId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view CFM effectiveness'), 403);
    }

    public function render(CfmEffectivenessDashboardService $dashboard): View
    {
        $viewer = auth()->user();
        $cfm = $this->resolveCfm($viewer);

        return view('livewire.cfm-effectiveness.dashboard', [
            'data' => $dashboard->dashboardFor($viewer, $cfm),
            'agency' => $viewer->can('view CFM reports')
                ? $dashboard->agencyOverview($viewer)
                : null,
            'cfmOptions' => $this->cfmOptionsFor($viewer),
            'canSelectCfm' => $viewer->can('manage CFM evaluations'),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    private function cfmOptionsFor(User $viewer): Collection
    {
        if (! $viewer->can('manage CFM evaluations')) {
            return collect();
        }

        return User::query()
            ->role('certified-field-mentor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolveCfm(User $viewer): User
    {
        if ($this->cfmId && $viewer->can('manage CFM evaluations')) {
            return User::query()
                ->role('certified-field-mentor')
                ->findOrFail($this->cfmId);
        }

        if ($viewer->hasRole('certified-field-mentor')) {
            return $viewer;
        }

        abort_unless($viewer->can('manage CFM evaluations'), 403);

        return User::query()
            ->role('certified-field-mentor')
            ->orderBy('name')
            ->firstOrFail();
    }
}
