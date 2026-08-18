<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessActionPlan;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessImprovementService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CfmImprovementCenter extends Component
{
    #[Url]
    public ?int $cfmId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view CFM effectiveness'), 403);
    }

    public function generatePlans(CfmEffectivenessImprovementService $improvements): void
    {
        abort_unless(auth()->user()->can('manage action plans'), 403);

        $cfm = $this->resolveCfm();
        $improvements->generateActionPlans($cfm, auth()->user());

        session()->flash('cfm_effectiveness_status', 'Improvement action plans generated.');
    }

    public function updateProgress(int $planId, int $progress): void
    {
        $plan = CfmEffectivenessActionPlan::query()->findOrFail($planId);
        abort_unless($this->canManagePlan($plan), 403);

        $plan->update([
            'progress' => max(0, min(100, $progress)),
            'status' => $progress >= 100 ? 'completed' : 'active',
            'completed_at' => $progress >= 100 ? now() : null,
        ]);
    }

    public function render(CfmEffectivenessImprovementService $improvements): View
    {
        $cfm = $this->resolveCfm();

        return view('livewire.cfm-effectiveness.improvement-center', [
            'cfm' => $cfm,
            'recommendations' => $improvements->recommendationsFor($cfm),
            'actionPlans' => $improvements->activePlansFor($cfm),
        ]);
    }

    private function resolveCfm(): User
    {
        if ($this->cfmId && auth()->user()->can('manage CFM evaluations')) {
            return User::query()->findOrFail($this->cfmId);
        }

        return auth()->user();
    }

    private function canManagePlan(CfmEffectivenessActionPlan $plan): bool
    {
        $user = auth()->user();

        return $plan->cfm_id === $user->id || $user->can('manage action plans');
    }
}
