<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalPlanningSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoalPlanningSettingsPanel extends Component
{
    public float $incomeCommissionPercent = 20;

    public float $avgAnnualPremiumPerApplication = 2500;

    public int $workingDaysPerMonth = 22;

    public int $workingWeeksPerYear = 48;

    public float $weeksPerMonth = 4.33;

  /** @var array<string, float> */
    public array $conversionRates = [];

    public string $previewIncome = '100000';

    public function mount(GoalPlanningSettingsService $settings): void
    {
        $this->authorize('viewAny', Goal::class);
        $this->loadFromService($settings);
    }

    public function save(GoalPlanningSettingsService $settings): void
    {
        $this->authorize('viewAny', Goal::class);

        $validated = $this->validate([
            'incomeCommissionPercent' => ['required', 'numeric', 'min:1', 'max:100'],
            'avgAnnualPremiumPerApplication' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'workingDaysPerMonth' => ['required', 'integer', 'min:1', 'max:31'],
            'workingWeeksPerYear' => ['required', 'integer', 'min:1', 'max:52'],
            'weeksPerMonth' => ['required', 'numeric', 'min:1', 'max:5'],
            'conversionRates' => ['array'],
            'conversionRates.*' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        $settings->save(auth()->user(), [
            'income_commission_percent' => $validated['incomeCommissionPercent'],
            'avg_annual_premium_per_application' => $validated['avgAnnualPremiumPerApplication'],
            'working_days_per_month' => $validated['workingDaysPerMonth'],
            'working_weeks_per_year' => $validated['workingWeeksPerYear'],
            'weeks_per_month' => $validated['weeksPerMonth'],
            'conversion_rates' => $this->conversionRates,
        ]);

        session()->flash('goals_status', 'Planning calculation settings saved.');
    }

    public function resetDefaults(GoalPlanningSettingsService $settings): void
    {
        $this->authorize('viewAny', Goal::class);

        $settings->resetToDefaults(auth()->user());
        $this->loadFromService($settings);

        session()->flash('goals_status', 'Planning settings restored to system defaults.');
    }

    public function render(): View
    {
        $previewIncomeValue = max(1, (float) $this->previewIncome);

        return view('livewire.goals.goal-planning-settings-panel', [
            'settingsFields' => config('goals-planning.settings_fields', []),
            'editableConversionRates' => config('goals-planning.editable_conversion_rates', []),
            'preview' => $this->previewFromForm($previewIncomeValue),
            'hasCustomSettings' => app(GoalPlanningSettingsService::class)->formStateFor(auth()->user())['has_custom_settings'],
        ]);
    }

    /**
     * @return array{income: float, production: float, applications: float, commission_percent: float}
     */
    private function previewFromForm(float $annualIncome): array
    {
        $commission = max($this->incomeCommissionPercent / 100, 0.01);
        $avgPremium = max($this->avgAnnualPremiumPerApplication, 1);
        $production = $annualIncome / $commission;

        return [
            'income' => round($annualIncome, 0),
            'production' => round($production, 0),
            'applications' => round($production / $avgPremium, 1),
            'commission_percent' => round($this->incomeCommissionPercent, 1),
        ];
    }

    private function loadFromService(GoalPlanningSettingsService $settings): void
    {
        $state = $settings->formStateFor(auth()->user());

        $this->incomeCommissionPercent = (float) $state['income_commission_percent'];
        $this->avgAnnualPremiumPerApplication = (float) $state['avg_annual_premium_per_application'];
        $this->workingDaysPerMonth = (int) $state['working_days_per_month'];
        $this->workingWeeksPerYear = (int) $state['working_weeks_per_year'];
        $this->weeksPerMonth = (float) $state['weeks_per_month'];
        $this->conversionRates = $state['conversion_rates'];
    }
}
