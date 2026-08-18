<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\DimeCalculatorService;
use App\Services\Fna\FnaAiAssistantService;
use App\Services\Fna\FnaRecordService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DimeCalculator extends Component
{
    public ?string $fnaRecordId = null;

    public array $dime = [];

    public array $dimeResult = [];

    public string $saveStatus = '';

    public function mount(?string $fna = null): void
    {
        $defaults = config('fna.dime_defaults', []);

        $this->fnaRecordId = $fna;
        $this->dime = [
            'credit_card_debt' => null,
            'personal_loans' => null,
            'car_loans' => null,
            'student_loans' => null,
            'business_debt' => null,
            'final_expenses' => null,
            'other_debt' => null,
            'income_annual_to_replace' => null,
            'income_years_to_replace' => $defaults['income_replacement_years'] ?? 10,
            'income_inflation_adjustment' => true,
            'existing_income_replacement_coverage' => null,
            'mortgage_balance' => null,
            'mortgage_years_remaining' => null,
            'monthly_mortgage_payment' => null,
            'include_mortgage_payoff' => true,
            'education_children_count' => null,
            'education_cost_per_child' => $defaults['education_cost_per_child'] ?? 100000,
            'education_years_to_college' => null,
            'education_inflation_adjustment' => true,
            'existing_education_savings' => null,
            'existing_life_insurance' => null,
            'liquid_assets_allocated' => null,
            'notes' => null,
        ];

        if ($fna) {
            $record = FnaRecord::with('dimeAnalysis', 'debtDetail', 'incomeDetail', 'household', 'assets', 'existingCoverage')
                ->findOrFail($fna);
            $this->authorize('view', $record);
            $this->prefillFromRecord($record);
        }

        $this->recalculate();
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'dime')) {
            $this->recalculate();
        }

        if ($property === 'fnaRecordId' && $this->fnaRecordId) {
            $record = FnaRecord::with('dimeAnalysis', 'debtDetail', 'incomeDetail', 'household', 'assetDetail', 'existingCoverage')
                ->find($this->fnaRecordId);

            if ($record) {
                $this->authorize('view', $record);
                $this->prefillFromRecord($record);
                $this->recalculate();
            }
        }
    }

    public function save(DimeCalculatorService $dime, FnaRecordService $records): void
    {
        if (! $this->fnaRecordId) {
            $this->addError('fnaRecordId', 'Select an FNA record to save results.');

            return;
        }

        $record = FnaRecord::findOrFail($this->fnaRecordId);
        $this->authorize('update', $record);

        $dime->saveToFna($record, $this->dime, $this->dime['notes'] ?? null);
        $this->saveStatus = 'Saved to '.$record->reference_code.' at '.now()->format('g:i A');
    }

    public function render(): View
    {
        $fnas = auth()->user()
            ? FnaRecord::query()
                ->where('owner_user_id', auth()->id())
                ->latest()
                ->limit(50)
                ->get(['id', 'reference_code', 'client_name'])
            : collect();

        $gapSummary = null;
        $complianceNotice = null;

        if ($this->fnaRecordId) {
            $record = FnaRecord::with(['goals', 'riskAssessment', 'dimeAnalysis'])->find($this->fnaRecordId);
            $ai = app(FnaAiAssistantService::class);

            if ($record && $ai->isEnabled('protection_gap_summary')) {
                $gapSummary = $ai->protectionGapSummary($record, auth()->user(), $this->dimeResult);
                $complianceNotice = $ai->complianceNotice();
            }
        }

        return view('livewire.fna.dime-calculator', compact('fnas', 'gapSummary', 'complianceNotice'));
    }

    protected function prefillFromRecord(FnaRecord $record): void
    {
        $analysis = $record->dimeAnalysis;

        if ($analysis) {
            $this->dime = array_merge($this->dime, [
                'credit_card_debt' => $analysis->debt_inputs['credit_card_debt'] ?? null,
                'personal_loans' => $analysis->debt_inputs['personal_loans'] ?? null,
                'car_loans' => $analysis->debt_inputs['car_loans'] ?? null,
                'student_loans' => $analysis->debt_inputs['student_loans'] ?? null,
                'business_debt' => $analysis->debt_inputs['business_debt'] ?? null,
                'final_expenses' => $analysis->debt_inputs['final_expenses'] ?? null,
                'other_debt' => $analysis->debt_inputs['other_debt'] ?? null,
                'income_annual_to_replace' => $analysis->income_annual_to_replace,
                'income_years_to_replace' => $analysis->income_years_to_replace,
                'income_inflation_adjustment' => $analysis->income_inflation_adjustment,
                'existing_income_replacement_coverage' => $analysis->existing_income_replacement_coverage,
                'mortgage_balance' => $analysis->mortgage_balance,
                'mortgage_years_remaining' => $analysis->mortgage_years_remaining,
                'monthly_mortgage_payment' => $analysis->monthly_mortgage_payment,
                'include_mortgage_payoff' => $analysis->include_mortgage_payoff,
                'education_children_count' => $analysis->education_children_count,
                'education_cost_per_child' => $analysis->education_cost_per_child,
                'education_years_to_college' => $analysis->education_years_to_college,
                'education_inflation_adjustment' => $analysis->education_inflation_adjustment,
                'existing_education_savings' => $analysis->existing_education_savings,
                'existing_life_insurance' => $analysis->existing_life_insurance,
                'liquid_assets_allocated' => $analysis->liquid_assets_allocated,
                'notes' => $analysis->notes,
            ]);
        } else {
            $this->dime['credit_card_debt'] = $record->debtDetail?->credit_card_debt;
            $this->dime['car_loans'] = $record->debtDetail?->car_loans;
            $this->dime['student_loans'] = $record->debtDetail?->student_loans;
            $this->dime['business_debt'] = $record->debtDetail?->business_debt;
            $this->dime['mortgage_balance'] = $record->debtDetail?->mortgage_balance;
            $this->dime['income_annual_to_replace'] = $record->incomeDetail?->annual_income;
            $this->dime['education_children_count'] = $record->household?->children_count;
            $this->dime['existing_education_savings'] = $record->assetDetail?->college_savings;
            $this->dime['existing_life_insurance'] = $record->existingCoverage?->existing_life_insurance_amount;
        }
    }

    protected function recalculate(): void
    {
        $this->dimeResult = app(DimeCalculatorService::class)->calculate($this->dime);
    }
}
