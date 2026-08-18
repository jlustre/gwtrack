<?php

namespace App\Services\Fna;

use App\Models\FnaClientInvite;
use App\Models\FnaRecord;

class FnaClientPortalService
{
    public function __construct(
        private FnaRecordService $records,
    ) {}

    public function saveProgress(FnaClientInvite $invite, array $data): FnaRecord
    {
        $fna = $this->records->saveWizardData($invite->fnaRecord, $data);

        $invite->update([
            'last_saved_at' => now(),
            'status' => in_array($invite->status, ['pending', 'active'], true) ? 'active' : $invite->status,
        ]);

        return $fna;
    }

    public function getWizardState(FnaRecord $fna): array
    {
        $fna->load([
            'household', 'incomeDetail', 'debtDetail', 'assetDetail',
            'existingCoverage', 'goals', 'riskAssessment',
        ]);

        return [
            'currentStep' => max(1, min(9, (int) $fna->current_step)),
            'client_name' => $fna->client_name ?? '',
            'client_email' => $fna->client_email,
            'client_phone' => $fna->client_phone,
            'date_of_birth' => $fna->date_of_birth?->format('Y-m-d'),
            'gender' => $fna->gender,
            'marital_status' => $fna->marital_status,
            'occupation' => $fna->occupation,
            'employer_business' => $fna->employer_business,
            'city' => $fna->city,
            'state_province' => $fna->state_province,
            'country' => $fna->country,
            'preferred_contact_method' => $fna->preferred_contact_method,
            'best_contact_time' => $fna->best_contact_time,
            'main_needs_identified' => $fna->main_needs_identified,
            'recommended_next_action' => $fna->recommended_next_action,
            'follow_up_date' => $fna->follow_up_date?->format('Y-m-d'),
            'associate_recommendation' => $fna->associate_recommendation,
            'summary_notes' => $fna->summary_notes,
            'household' => $fna->household?->only([
                'spouse_partner_name', 'spouse_partner_age', 'children_count', 'dependents_notes',
                'household_income', 'household_expenses', 'financial_priorities',
            ]) ?? [],
            'income' => $fna->incomeDetail?->only([
                'annual_income', 'monthly_income', 'spouse_annual_income', 'business_income',
                'passive_income', 'expected_income_changes',
            ]) ?? [],
            'debt' => $fna->debtDetail?->only([
                'mortgage_balance', 'rent_amount', 'credit_card_debt', 'car_loans', 'student_loans',
                'personal_loans', 'business_debt', 'other_liabilities',
            ]) ?? [],
            'assets' => $fna->assetDetail?->only([
                'emergency_fund', 'checking_savings', 'retirement_accounts', 'investment_accounts',
                'real_estate_assets', 'business_assets', 'college_savings', 'other_assets',
            ]) ?? [],
            'coverage' => $fna->existingCoverage?->only([
                'existing_life_insurance_amount', 'term_coverage', 'whole_life_coverage',
                'universal_life_coverage', 'group_insurance_coverage', 'disability_coverage',
                'critical_illness_coverage', 'long_term_care_coverage', 'beneficiary_information',
                'policy_review_needed',
            ]) ?? [],
            'selected_goals' => $fna->goals?->selected_goals ?? [],
            'goal_notes' => $fna->goals?->goal_notes,
            'risk' => $fna->riskAssessment?->only([
                'main_financial_concern', 'health_considerations', 'job_stability',
                'family_dependency_level', 'emergency_fund_adequacy', 'current_protection_gap',
                'risk_tolerance', 'urgency_level',
            ]) ?? [],
            'completenessScore' => $fna->completeness_score,
        ];
    }
}
