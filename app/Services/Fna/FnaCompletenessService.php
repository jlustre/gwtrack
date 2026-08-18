<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;

class FnaCompletenessService
{
    public function score(FnaRecord $fna): int
    {
        $fna->loadMissing([
            'household',
            'incomeDetail',
            'debtDetail',
            'assetDetail',
            'existingCoverage',
            'goals',
            'riskAssessment',
        ]);

        $filled = 0;
        $total = 0;

        foreach (config('fna.step_field_map', []) as $fields) {
            foreach ($fields as $field) {
                $total++;
                if ($this->fieldHasValue($fna, $field)) {
                    $filled++;
                }
            }
        }

        if ($fna->dime_completed) {
            $filled++;
        }
        $total++;

        if ($total === 0) {
            return 0;
        }

        return (int) round(($filled / $total) * 100);
    }

    public function missingSections(FnaRecord $fna): array
    {
        $missing = [];

        foreach (config('fna.step_field_map', []) as $step => $fields) {
            $stepFilled = collect($fields)->contains(fn (string $field): bool => $this->fieldHasValue($fna, $field));

            if (! $stepFilled) {
                $missing[] = config('fna.wizard_steps')[$step] ?? "Step {$step}";
            }
        }

        if (! $fna->dime_completed) {
            $missing[] = 'DIME Analysis';
        }

        return $missing;
    }

    public function meetsThreshold(FnaRecord $fna): bool
    {
        return $this->score($fna) >= (int) config('fna.completeness_threshold', 60);
    }

    protected function fieldHasValue(FnaRecord $fna, string $field): bool
    {
        $value = $this->resolveFieldValue($fna, $field);

        if (is_array($value)) {
            return count($value) > 0;
        }

        if ($value === null || $value === '') {
            return false;
        }

        return true;
    }

    protected function resolveFieldValue(FnaRecord $fna, string $field): mixed
    {
        $recordFields = [
            'client_name', 'client_email', 'client_phone', 'date_of_birth', 'occupation',
            'city', 'state_province', 'country', 'main_needs_identified', 'recommended_next_action',
            'associate_recommendation',
        ];

        if (in_array($field, $recordFields, true)) {
            return $fna->{$field};
        }

        $householdFields = ['spouse_partner_name', 'household_income', 'household_expenses', 'children_count'];
        if (in_array($field, $householdFields, true)) {
            return $fna->household?->{$field};
        }

        $incomeFields = ['annual_income', 'monthly_income'];
        if (in_array($field, $incomeFields, true)) {
            return $fna->incomeDetail?->{$field};
        }

        $debtFields = ['mortgage_balance', 'credit_card_debt', 'total_debt'];
        if (in_array($field, $debtFields, true)) {
            return $fna->debtDetail?->{$field};
        }

        $assetFields = ['checking_savings', 'retirement_accounts', 'emergency_fund'];
        if (in_array($field, $assetFields, true)) {
            return $fna->assetDetail?->{$field};
        }

        $coverageFields = ['existing_life_insurance_amount', 'term_coverage'];
        if (in_array($field, $coverageFields, true)) {
            return $fna->existingCoverage?->{$field};
        }

        if ($field === 'selected_goals') {
            return $fna->goals?->selected_goals ?? [];
        }

        $riskFields = ['main_financial_concern', 'urgency_level', 'risk_tolerance'];
        if (in_array($field, $riskFields, true)) {
            return $fna->riskAssessment?->{$field};
        }

        return null;
    }
}
