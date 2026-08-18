<?php

namespace App\Services\Fna;

use App\Models\FnaDimeAnalysis;
use App\Models\FnaRecord;

class DimeCalculatorService
{
    public function __construct(
        private FnaRecordService $records,
        private FnaCompletenessService $completeness,
    ) {}

    public function calculate(array $inputs): array
    {
        $defaults = config('fna.dime_defaults', []);
        $multipliers = $defaults['coverage_range_multiplier'] ?? [0.9, 1.1];

        $debtInputs = [
            'credit_card_debt' => (float) ($inputs['credit_card_debt'] ?? 0),
            'personal_loans' => (float) ($inputs['personal_loans'] ?? 0),
            'car_loans' => (float) ($inputs['car_loans'] ?? 0),
            'student_loans' => (float) ($inputs['student_loans'] ?? 0),
            'business_debt' => (float) ($inputs['business_debt'] ?? 0),
            'final_expenses' => (float) ($inputs['final_expenses'] ?? 0),
            'other_debt' => (float) ($inputs['other_debt'] ?? 0),
        ];
        $totalDebt = array_sum($debtInputs);

        $annualIncome = (float) ($inputs['income_annual_to_replace'] ?? 0);
        $years = (int) ($inputs['income_years_to_replace'] ?? $defaults['income_replacement_years'] ?? 10);
        $inflation = (float) ($defaults['inflation_rate'] ?? 0.03);
        $useIncomeInflation = (bool) ($inputs['income_inflation_adjustment'] ?? true);
        $existingIncomeCoverage = (float) ($inputs['existing_income_replacement_coverage'] ?? 0);

        $incomeMultiplier = $useIncomeInflation ? pow(1 + $inflation, $years) : 1;
        $totalIncomeNeed = max(0, ($annualIncome * $years * $incomeMultiplier) - $existingIncomeCoverage);

        $mortgageBalance = (float) ($inputs['mortgage_balance'] ?? 0);
        $includeMortgage = (bool) ($inputs['include_mortgage_payoff'] ?? true);
        $totalMortgageNeed = $includeMortgage ? $mortgageBalance : 0;

        $childrenCount = (int) ($inputs['education_children_count'] ?? 0);
        $costPerChild = (float) ($inputs['education_cost_per_child'] ?? $defaults['education_cost_per_child'] ?? 100000);
        $yearsToCollege = (int) ($inputs['education_years_to_college'] ?? 0);
        $educationInflation = (float) ($defaults['education_inflation_rate'] ?? 0.05);
        $useEducationInflation = (bool) ($inputs['education_inflation_adjustment'] ?? true);
        $existingEducationSavings = (float) ($inputs['existing_education_savings'] ?? 0);

        $educationMultiplier = $useEducationInflation ? pow(1 + $educationInflation, max(0, $yearsToCollege)) : 1;
        $totalEducationNeed = max(0, ($childrenCount * $costPerChild * $educationMultiplier) - $existingEducationSavings);

        $totalDimeNeed = $totalDebt + $totalIncomeNeed + $totalMortgageNeed + $totalEducationNeed;

        $existingLife = (float) ($inputs['existing_life_insurance'] ?? 0);
        $liquidAssets = (float) ($inputs['liquid_assets_allocated'] ?? 0);
        $protectionGap = max(0, $totalDimeNeed - $existingLife - $liquidAssets);

        $minCoverage = $protectionGap * ($multipliers[0] ?? 0.9);
        $maxCoverage = $protectionGap * ($multipliers[1] ?? 1.1);

        return [
            'debt_inputs' => $debtInputs,
            'total_debt' => round($totalDebt, 2),
            'total_income_need' => round($totalIncomeNeed, 2),
            'total_mortgage_need' => round($totalMortgageNeed, 2),
            'total_education_need' => round($totalEducationNeed, 2),
            'total_dime_need' => round($totalDimeNeed, 2),
            'existing_life_insurance' => round($existingLife, 2),
            'liquid_assets_allocated' => round($liquidAssets, 2),
            'estimated_protection_gap' => round($protectionGap, 2),
            'recommended_coverage_min' => round($minCoverage, 2),
            'recommended_coverage_max' => round($maxCoverage, 2),
        ];
    }

    public function saveToFna(FnaRecord $fna, array $inputs, ?string $notes = null): FnaDimeAnalysis
    {
        $result = $this->calculate($inputs);

        $analysis = $fna->dimeAnalysis()->updateOrCreate(
            ['fna_record_id' => $fna->id],
            [
                'debt_inputs' => $result['debt_inputs'],
                'total_debt' => $result['total_debt'],
                'income_annual_to_replace' => $inputs['income_annual_to_replace'] ?? null,
                'income_years_to_replace' => $inputs['income_years_to_replace'] ?? null,
                'income_inflation_adjustment' => (bool) ($inputs['income_inflation_adjustment'] ?? true),
                'existing_income_replacement_coverage' => $inputs['existing_income_replacement_coverage'] ?? null,
                'total_income_need' => $result['total_income_need'],
                'mortgage_balance' => $inputs['mortgage_balance'] ?? null,
                'mortgage_years_remaining' => $inputs['mortgage_years_remaining'] ?? null,
                'monthly_mortgage_payment' => $inputs['monthly_mortgage_payment'] ?? null,
                'include_mortgage_payoff' => (bool) ($inputs['include_mortgage_payoff'] ?? true),
                'total_mortgage_need' => $result['total_mortgage_need'],
                'education_children_count' => $inputs['education_children_count'] ?? null,
                'education_cost_per_child' => $inputs['education_cost_per_child'] ?? null,
                'education_years_to_college' => $inputs['education_years_to_college'] ?? null,
                'education_inflation_adjustment' => (bool) ($inputs['education_inflation_adjustment'] ?? true),
                'existing_education_savings' => $inputs['existing_education_savings'] ?? null,
                'total_education_need' => $result['total_education_need'],
                'total_dime_need' => $result['total_dime_need'],
                'existing_life_insurance' => $result['existing_life_insurance'],
                'liquid_assets_allocated' => $result['liquid_assets_allocated'],
                'estimated_protection_gap' => $result['estimated_protection_gap'],
                'recommended_coverage_min' => $result['recommended_coverage_min'],
                'recommended_coverage_max' => $result['recommended_coverage_max'],
                'notes' => $notes,
                'calculated_at' => now(),
            ]
        );

        $fna->update([
            'dime_completed' => true,
            'protection_gap' => $result['estimated_protection_gap'],
            'recommended_coverage_min' => $result['recommended_coverage_min'],
            'recommended_coverage_max' => $result['recommended_coverage_max'],
            'completeness_score' => $this->completeness->score($fna->fresh()),
        ]);

        $this->records->logActivity($fna, auth()->user(), 'dime_calculated', 'DIME analysis saved.', $result);

        return $analysis;
    }
}
