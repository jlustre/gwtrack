<?php

namespace App\Livewire\Fna;

use App\Livewire\Concerns\InteractsWithFnaClientInfoFields;
use App\Models\FnaRecord;
use App\Services\Fna\DimeCalculatorService;
use App\Services\Fna\FnaAiAssistantService;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaWorkflowService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaWizard extends Component
{
    use InteractsWithFnaClientInfoFields;

    public FnaRecord $fna;

    public int $currentStep = 1;

    public string $activeTab = 'wizard';

    public string $saveStatus = '';

    public int $completenessScore = 0;

    public string $client_name = '';

    public ?string $client_email = null;

    public ?string $client_phone = null;

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public ?string $marital_status = null;

    public ?string $occupation = null;

    public ?string $employer_business = null;

    public ?string $city = null;

    public ?string $state_province = null;

    public ?string $country = null;

    public ?string $preferred_contact_method = null;

    public ?string $best_contact_time = null;

    public array $household = [];

    public array $income = [];

    public array $debt = [];

    public array $assets = [];

    public array $coverage = [];

    public array $selected_goals = [];

    public ?string $goal_notes = null;

    public array $risk = [];

    public ?string $main_needs_identified = null;

    public ?string $recommended_next_action = null;

    public ?string $follow_up_date = null;

    public ?string $associate_recommendation = null;

    public ?string $summary_notes = null;

    public array $dime = [];

    public array $dimeResult = [];

    protected bool $isHydrating = false;

    public function mount(FnaRecord $fna): void
    {
        $this->authorize('view', $fna);

        if (! $fna->isEditableByOwner()) {
            abort_unless(auth()->user()?->can('update', $fna), 403);
        }

        $this->isHydrating = true;
        $this->fna = $fna->load([
            'household', 'incomeDetail', 'debtDetail', 'assetDetail',
            'existingCoverage', 'goals', 'riskAssessment', 'dimeAnalysis',
        ]);

        $this->currentStep = max(1, min(9, (int) $this->fna->current_step));
        $this->hydrateFromRecord();
        $this->refreshCompleteness();
        $this->isHydrating = false;
    }

    public function updated($property): void
    {
        if ($this->isHydrating || in_array($property, ['currentStep', 'activeTab', 'saveStatus', 'completenessScore', 'dimeResult'], true)) {
            return;
        }

        if (str_starts_with($property, 'dime')) {
            $this->recalculateDimePreview();

            return;
        }

        $this->autosave();
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = max(1, min(9, $step));
        $this->autosave();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 9) {
            $this->currentStep++;
            $this->autosave();
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->autosave();
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['wizard', 'dime'], true) ? $tab : 'wizard';

        if ($tab === 'dime') {
            $this->recalculateDimePreview();
        }
    }

    public function autosave(): void
    {
        $this->authorize('update', $this->fna);

        $this->fna = app(FnaRecordService::class)->saveWizardData($this->fna, $this->payload());
        $this->saveStatus = 'Saved '.now()->format('g:i A');
        $this->refreshCompleteness();
    }

    public function saveDime(DimeCalculatorService $dime): void
    {
        $this->authorize('update', $this->fna);

        $dime->saveToFna($this->fna, $this->dime, $this->dime['notes'] ?? null);
        $this->fna->refresh();
        $this->recalculateDimePreview();
        $this->refreshCompleteness();
        $this->saveStatus = 'DIME analysis saved '.now()->format('g:i A');
    }

    public function markReadyForReview(
        FnaCompletenessService $completeness,
        FnaWorkflowService $workflow,
    ): void {
        $this->authorize('update', $this->fna);

        $this->autosave();
        $this->fna->refresh();

        if (! $completeness->meetsThreshold($this->fna)) {
            $this->addError('completeness', 'Complete more sections before marking ready for review (minimum '.config('fna.completeness_threshold').'%).');

            return;
        }

        if ($this->fna->status === 'draft' || $this->fna->status === 'revision_requested') {
            $this->fna = $workflow->transition($this->fna, auth()->user(), 'ready_for_review');
        }

        session()->flash('fna_status', 'FNA marked ready for review. Submit to your CFM when you are ready.');

        $this->redirect(route('team.fna.show', $this->fna));
    }

    public function render(): View
    {
        $ai = app(FnaAiAssistantService::class);
        $gapSummary = null;
        $complianceNotice = null;

        if ($ai->isEnabled('protection_gap_summary')) {
            $gapSummary = $ai->protectionGapSummary($this->fna, auth()->user(), $this->dimeResult);
            $complianceNotice = $ai->complianceNotice();
        }

        return view('livewire.fna.fna-wizard', [
            'steps' => config('fna.wizard_steps', []),
            'goalOptions' => config('fna.goal_options', []),
            'missingSections' => app(FnaCompletenessService::class)->missingSections($this->fna),
            'gapSummary' => $gapSummary,
            'complianceNotice' => $complianceNotice,
            'clientInfoFieldOptions' => $this->fnaClientInfoFieldOptions(clientPortal: false),
        ]);
    }

    protected function hydrateFromRecord(): void
    {
        $r = $this->fna;

        $this->client_name = $r->client_name ?? '';
        $this->client_email = $r->client_email;
        $this->client_phone = $r->client_phone;
        $this->date_of_birth = $r->date_of_birth?->format('Y-m-d');
        $this->gender = $r->gender;
        $this->marital_status = $r->marital_status;
        $this->occupation = $r->occupation;
        $this->employer_business = $r->employer_business;
        $this->city = $r->city;
        $this->state_province = $r->state_province;
        $this->country = $r->country;
        $this->preferred_contact_method = $r->preferred_contact_method;
        $this->best_contact_time = $r->best_contact_time;
        $this->main_needs_identified = $r->main_needs_identified;
        $this->recommended_next_action = $r->recommended_next_action;
        $this->follow_up_date = $r->follow_up_date?->format('Y-m-d');
        $this->associate_recommendation = $r->associate_recommendation;
        $this->summary_notes = $r->summary_notes;

        $this->household = $r->household?->only([
            'spouse_partner_name', 'spouse_partner_age', 'children_count', 'dependents_notes',
            'household_income', 'household_expenses', 'financial_priorities',
        ]) ?? [];

        $this->income = $r->incomeDetail?->only([
            'annual_income', 'monthly_income', 'spouse_annual_income', 'business_income',
            'passive_income', 'expected_income_changes',
        ]) ?? [];

        $this->debt = $r->debtDetail?->only([
            'mortgage_balance', 'rent_amount', 'credit_card_debt', 'car_loans', 'student_loans',
            'personal_loans', 'business_debt', 'other_liabilities',
        ]) ?? [];

        $this->assets = $r->assetDetail?->only([
            'emergency_fund', 'checking_savings', 'retirement_accounts', 'investment_accounts',
            'real_estate_assets', 'business_assets', 'college_savings', 'other_assets',
        ]) ?? [];

        $this->coverage = $r->existingCoverage?->only([
            'existing_life_insurance_amount', 'term_coverage', 'whole_life_coverage',
            'universal_life_coverage', 'group_insurance_coverage', 'disability_coverage',
            'critical_illness_coverage', 'long_term_care_coverage', 'beneficiary_information',
            'policy_review_needed',
        ]) ?? [];

        $this->selected_goals = $r->goals?->selected_goals ?? [];
        $this->goal_notes = $r->goals?->goal_notes;

        $this->risk = $r->riskAssessment?->only([
            'main_financial_concern', 'health_considerations', 'job_stability',
            'family_dependency_level', 'emergency_fund_adequacy', 'current_protection_gap',
            'risk_tolerance', 'urgency_level',
        ]) ?? [];

        $analysis = $r->dimeAnalysis;
        $this->dime = [
            'credit_card_debt' => $analysis?->debt_inputs['credit_card_debt'] ?? $this->debt['credit_card_debt'] ?? null,
            'personal_loans' => $analysis?->debt_inputs['personal_loans'] ?? $this->debt['personal_loans'] ?? null,
            'car_loans' => $analysis?->debt_inputs['car_loans'] ?? $this->debt['car_loans'] ?? null,
            'student_loans' => $analysis?->debt_inputs['student_loans'] ?? $this->debt['student_loans'] ?? null,
            'business_debt' => $analysis?->debt_inputs['business_debt'] ?? $this->debt['business_debt'] ?? null,
            'final_expenses' => $analysis?->debt_inputs['final_expenses'] ?? null,
            'other_debt' => $analysis?->debt_inputs['other_debt'] ?? null,
            'income_annual_to_replace' => $analysis?->income_annual_to_replace ?? $this->income['annual_income'] ?? null,
            'income_years_to_replace' => $analysis?->income_years_to_replace ?? config('fna.dime_defaults.income_replacement_years'),
            'income_inflation_adjustment' => $analysis?->income_inflation_adjustment ?? true,
            'existing_income_replacement_coverage' => $analysis?->existing_income_replacement_coverage ?? null,
            'mortgage_balance' => $analysis?->mortgage_balance ?? $this->debt['mortgage_balance'] ?? null,
            'mortgage_years_remaining' => $analysis?->mortgage_years_remaining ?? null,
            'monthly_mortgage_payment' => $analysis?->monthly_mortgage_payment ?? null,
            'include_mortgage_payoff' => $analysis?->include_mortgage_payoff ?? true,
            'education_children_count' => $analysis?->education_children_count ?? $this->household['children_count'] ?? null,
            'education_cost_per_child' => $analysis?->education_cost_per_child ?? config('fna.dime_defaults.education_cost_per_child'),
            'education_years_to_college' => $analysis?->education_years_to_college ?? null,
            'education_inflation_adjustment' => $analysis?->education_inflation_adjustment ?? true,
            'existing_education_savings' => $analysis?->existing_education_savings ?? $this->assets['college_savings'] ?? null,
            'existing_life_insurance' => $analysis?->existing_life_insurance ?? $this->coverage['existing_life_insurance_amount'] ?? null,
            'liquid_assets_allocated' => $analysis?->liquid_assets_allocated ?? null,
            'notes' => $analysis?->notes ?? null,
        ];

        $this->recalculateDimePreview();
    }

    protected function payload(): array
    {
        return [
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_phone' => $this->client_phone,
            'date_of_birth' => $this->date_of_birth ?: null,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'occupation' => $this->occupation,
            'employer_business' => $this->employer_business,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'country' => $this->country,
            'preferred_contact_method' => $this->preferred_contact_method,
            'best_contact_time' => $this->best_contact_time,
            'main_needs_identified' => $this->main_needs_identified,
            'recommended_next_action' => $this->recommended_next_action,
            'follow_up_date' => $this->follow_up_date ?: null,
            'associate_recommendation' => $this->associate_recommendation,
            'summary_notes' => $this->summary_notes,
            'current_step' => $this->currentStep,
            'household' => $this->normalizeNumericArray($this->household),
            'income' => $this->normalizeNumericArray($this->income),
            'debt' => $this->normalizeNumericArray($this->debt),
            'assets' => $this->normalizeNumericArray($this->assets),
            'coverage' => $this->normalizeCoverage($this->coverage),
            'goals' => [
                'selected_goals' => array_values($this->selected_goals),
                'goal_notes' => $this->goal_notes,
            ],
            'risk' => $this->risk,
        ];
    }

    protected function normalizeNumericArray(array $data): array
    {
        return collect($data)->map(function ($value, $key) {
            if (in_array($key, ['expected_income_changes', 'dependents_notes', 'beneficiary_information'], true)) {
                return $value;
            }

            return $value === '' || $value === null ? null : $value;
        })->all();
    }

    protected function normalizeCoverage(array $data): array
    {
        $data = $this->normalizeNumericArray($data);
        $data['policy_review_needed'] = (bool) ($data['policy_review_needed'] ?? false);

        return $data;
    }

    protected function recalculateDimePreview(): void
    {
        $this->dimeResult = app(DimeCalculatorService::class)->calculate($this->dime);
    }

    protected function refreshCompleteness(): void
    {
        $this->completenessScore = app(FnaCompletenessService::class)->score($this->fna);
    }
}
