<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmAoEvaluation;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmAoEvaluationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class AoEvaluationCenter extends Component
{
    #[Url]
    public ?int $cfmId = null;

    /** @var array<string, int> */
    public array $categoryScores = [];

    public string $strengths = '';

    public string $improvementAreas = '';

    public string $recommendations = '';

    public string $promotionPotential = '';

    public string $leadershipPotential = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage CFM evaluations'), 403);

        foreach (array_keys(config('cfm-effectiveness.ao_scorecard_categories', [])) as $key) {
            $this->categoryScores[$key] = 3;
        }
    }

    public function submit(CfmAoEvaluationService $evaluations): void
    {
        $this->validate([
            'cfmId' => ['required', 'integer', 'exists:users,id'],
            'categoryScores' => ['required', 'array'],
            'categoryScores.*' => ['integer', 'min:1', 'max:5'],
        ]);

        $cfm = User::query()->findOrFail($this->cfmId);
        $periodStart = now()->startOfQuarter();
        $periodEnd = now()->endOfQuarter();

        $evaluations->submitEvaluation(
            $cfm,
            auth()->user(),
            $this->categoryScores,
            [
                'strengths' => $this->strengths,
                'improvement_areas' => $this->improvementAreas,
                'recommendations' => $this->recommendations,
                'promotion_potential' => $this->promotionPotential,
                'leadership_potential' => $this->leadershipPotential,
            ],
            Carbon::parse($periodStart),
            Carbon::parse($periodEnd),
        );

        session()->flash('cfm_effectiveness_status', 'Quarterly CFM evaluation submitted successfully.');

        $this->redirect(route('cfm.effectiveness.evaluations', ['cfmId' => $this->cfmId]), navigate: true);
    }

    public function render(): View
    {
        $cfms = User::role('certified-field-mentor')->orderBy('name')->get(['id', 'name']);

        return view('livewire.cfm-effectiveness.ao-evaluation-center', [
            'cfms' => $cfms,
            'categories' => config('cfm-effectiveness.ao_scorecard_categories', []),
            'recentEvaluations' => CfmAoEvaluation::query()
                ->with(['cfm', 'evaluator'])
                ->where('status', 'submitted')
                ->latest('submitted_at')
                ->limit(12)
                ->get(),
        ]);
    }
}
