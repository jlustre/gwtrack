<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class FnaExportService
{
    private const MASK = '[Restricted]';

    public function buildExportData(FnaRecord $fna, User $viewer): array
    {
        $fna->loadMissing([
            'owner:id,name',
            'cfm:id,name',
            'prospect:id,first_name,last_name',
            'household',
            'incomeDetail',
            'debtDetail',
            'assetDetail',
            'existingCoverage',
            'goals',
            'riskAssessment',
            'dimeAnalysis',
            'reviewComments.user:id,name',
            'statusHistories.changedBy:id,name',
        ]);

        $canViewFinancial = Gate::forUser($viewer)->allows('viewFinancialDetails', $fna);

        return [
            'fna' => $fna,
            'reference_code' => $fna->reference_code,
            'title' => $fna->title,
            'status' => $fna->statusLabel(),
            'completeness_score' => $fna->completeness_score,
            'generated_at' => now(),
            'viewer_name' => $viewer->name,
            'owner_name' => $fna->owner?->name,
            'cfm_name' => $fna->cfm?->name,
            'can_view_financial' => $canViewFinancial,
            'dime_disclaimer' => config('fna.dime_disclaimer'),
            'client' => $this->clientSection($fna, $canViewFinancial),
            'household' => $this->householdSection($fna, $canViewFinancial),
            'income' => $this->incomeSection($fna, $canViewFinancial),
            'debt' => $this->debtSection($fna, $canViewFinancial),
            'assets' => $this->assetsSection($fna, $canViewFinancial),
            'coverage' => $this->coverageSection($fna, $canViewFinancial),
            'goals' => $this->goalsSection($fna, $canViewFinancial),
            'risk' => $this->riskSection($fna, $canViewFinancial),
            'dime' => $this->dimeSection($fna, $canViewFinancial),
            'summary' => $this->summarySection($fna, $canViewFinancial),
            'cfm_feedback' => $fna->cfm_feedback_summary,
            'review_comments' => $fna->reviewComments
                ->reject(fn ($comment) => $comment->is_internal && (int) $fna->owner_user_id === (int) $viewer->id)
                ->map(fn ($comment) => [
                    'author' => $comment->user?->name ?? 'Unknown',
                    'body' => $comment->body,
                    'type' => $comment->comment_type,
                    'created_at' => $comment->created_at,
                ])
                ->values()
                ->all(),
            'status_history' => $fna->statusHistories->map(fn ($history) => [
                'from' => $history->from_status
                    ? (config('fna.statuses')[$history->from_status] ?? $history->from_status)
                    : 'New',
                'to' => config('fna.statuses')[$history->to_status] ?? $history->to_status,
                'changed_by' => $history->changedBy?->name ?? 'System',
                'created_at' => $history->created_at,
            ])->values()->all(),
        ];
    }

    public function renderHtml(FnaRecord $fna, User $viewer): string
    {
        return view('pdf.fna-export', $this->buildExportData($fna, $viewer))->render();
    }

    public function downloadPdf(FnaRecord $fna, User $viewer): Response
    {
        $pdf = Pdf::loadHTML($this->renderHtml($fna, $viewer))
            ->setPaper('letter', 'portrait');

        return $pdf->download('FNA-'.$fna->reference_code.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function clientSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        return [
            'name' => $fna->client_name,
            'email' => $canViewFinancial ? ($fna->client_email ?: '—') : self::MASK,
            'phone' => $canViewFinancial ? ($fna->client_phone ?: '—') : self::MASK,
            'date_of_birth' => $canViewFinancial
                ? ($fna->date_of_birth?->format('M j, Y') ?? '—')
                : self::MASK,
            'age' => $canViewFinancial ? ($fna->age ?? '—') : self::MASK,
            'gender' => $fna->gender ?: '—',
            'marital_status' => $fna->marital_status ?: '—',
            'occupation' => $fna->occupation ?: '—',
            'employer_business' => $fna->employer_business ?: '—',
            'location' => collect([$fna->city, $fna->state_province, $fna->country])->filter()->implode(', ') ?: '—',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function householdSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        $household = $fna->household;

        if (! $canViewFinancial) {
            return ['restricted' => true];
        }

        return [
            'restricted' => false,
            'spouse_partner_name' => $household?->spouse_partner_name ?: '—',
            'children_count' => $household?->children_count ?? '—',
            'household_income' => $this->money($household?->household_income),
            'household_expenses' => $this->money($household?->household_expenses),
            'dependents_notes' => $household?->dependents_notes ?: '—',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incomeSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        if (! $canViewFinancial) {
            return ['restricted' => true];
        }

        $income = $fna->incomeDetail;

        return [
            'restricted' => false,
            'annual_income' => $this->money($income?->annual_income),
            'monthly_income' => $this->money($income?->monthly_income),
            'spouse_annual_income' => $this->money($income?->spouse_annual_income),
            'business_income' => $this->money($income?->business_income),
            'passive_income' => $this->money($income?->passive_income),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function debtSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        if (! $canViewFinancial) {
            return ['restricted' => true];
        }

        $debt = $fna->debtDetail;

        return [
            'restricted' => false,
            'mortgage_balance' => $this->money($debt?->mortgage_balance),
            'credit_card_debt' => $this->money($debt?->credit_card_debt),
            'total_debt' => $this->money($debt?->total_debt),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assetsSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        if (! $canViewFinancial) {
            return ['restricted' => true];
        }

        $assets = $fna->assetDetail;

        return [
            'restricted' => false,
            'checking_savings' => $this->money($assets?->checking_savings),
            'retirement_accounts' => $this->money($assets?->retirement_accounts),
            'emergency_fund' => $this->money($assets?->emergency_fund),
            'total_assets' => $this->money($assets?->total_assets),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coverageSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        if (! $canViewFinancial) {
            return ['restricted' => true];
        }

        $coverage = $fna->existingCoverage;

        return [
            'restricted' => false,
            'existing_life_insurance_amount' => $this->money($coverage?->existing_life_insurance_amount),
            'term_coverage' => $this->money($coverage?->term_coverage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function goalsSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        $goals = $fna->goals;
        $selected = collect($goals?->selected_goals ?? [])
            ->map(fn ($key) => config('fna.goal_options')[$key] ?? $key)
            ->values()
            ->all();

        return [
            'selected_goals' => $selected ?: ['—'],
            'goal_notes' => $canViewFinancial ? ($goals?->goal_notes ?: '—') : self::MASK,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function riskSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        $risk = $fna->riskAssessment;

        return [
            'main_financial_concern' => $canViewFinancial ? ($risk?->main_financial_concern ?: '—') : self::MASK,
            'urgency_level' => $risk?->urgency_level ?: '—',
            'risk_tolerance' => $risk?->risk_tolerance ?: '—',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dimeSection(FnaRecord $fna, bool $canViewFinancial): array
    {
        if (! $canViewFinancial || ! $fna->dime_completed) {
            return [
                'completed' => $fna->dime_completed,
                'restricted' => ! $canViewFinancial,
            ];
        }

        $dime = $fna->dimeAnalysis;

        return [
            'completed' => true,
            'restricted' => false,
            'total_debt' => $this->money($dime?->total_debt),
            'total_income_need' => $this->money($dime?->total_income_need),
            'total_mortgage_need' => $this->money($dime?->total_mortgage_need),
            'total_education_need' => $this->money($dime?->total_education_need),
            'total_dime_need' => $this->money($dime?->total_dime_need),
            'estimated_protection_gap' => $this->money($dime?->estimated_protection_gap),
            'recommended_coverage_min' => $this->money($dime?->recommended_coverage_min),
            'recommended_coverage_max' => $this->money($dime?->recommended_coverage_max),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summarySection(FnaRecord $fna, bool $canViewFinancial): array
    {
        return [
            'main_needs_identified' => $canViewFinancial ? ($fna->main_needs_identified ?: '—') : self::MASK,
            'recommended_next_action' => $fna->recommended_next_action ?: '—',
            'associate_recommendation' => $canViewFinancial ? ($fna->associate_recommendation ?: '—') : self::MASK,
            'protection_gap' => $canViewFinancial ? $this->money($fna->protection_gap) : self::MASK,
            'submitted_at' => $fna->submitted_at?->format('M j, Y g:i A'),
            'approved_at' => $fna->approved_at?->format('M j, Y g:i A'),
        ];
    }

    private function money(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return '$'.number_format((float) $value, 0);
    }
}
