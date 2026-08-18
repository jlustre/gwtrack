<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class FnaAiAssistantService
{
    public function __construct(
        private FnaCompletenessService $completeness,
    ) {}

    public function isEnabled(?string $feature = null): bool
    {
        if (! config('fna.ai.enabled', false)) {
            return false;
        }

        if ($feature === null) {
            return true;
        }

        return (bool) config("fna.ai.features.{$feature}", false);
    }

    /**
     * @return list<array{priority: string, message: string, action: string}>
     */
    public function completenessSuggestions(FnaRecord $fna): array
    {
        if (! $this->isEnabled('completeness_checker')) {
            return [];
        }

        $fna->loadMissing([
            'household',
            'incomeDetail',
            'debtDetail',
            'assetDetail',
            'existingCoverage',
            'goals',
            'riskAssessment',
        ]);

        $suggestions = [];

        foreach (config('fna.ai.completeness_rules', []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $condition = $rule['condition'] ?? null;

            if (! is_string($condition) || ! $this->evaluateCondition($condition, $fna)) {
                continue;
            }

            $suggestions[] = [
                'priority' => $this->normalizePriority($rule['priority'] ?? 'medium'),
                'message' => (string) ($rule['message'] ?? 'Review this section before submission.'),
                'action' => (string) ($rule['action'] ?? 'review'),
            ];
        }

        return $this->sortByPriority($suggestions);
    }

    public function protectionGapSummary(FnaRecord $fna, ?User $viewer = null, ?array $liveResult = null): ?string
    {
        if (! $this->isEnabled('protection_gap_summary')) {
            return null;
        }

        $fna->loadMissing(['goals', 'riskAssessment', 'dimeAnalysis']);

        $gap = $this->resolveProtectionGap($fna, $liveResult);
        $hasDimeData = $fna->dime_completed || $gap > 0 || ($liveResult['total_dime_need'] ?? 0) > 0;

        if (! $hasDimeData) {
            return null;
        }

        $viewer ??= auth()->user();
        $canViewFinancial = $viewer
            ? Gate::forUser($viewer)->allows('viewFinancialDetails', $fna)
            : true;

        $clientName = $fna->client_name ?: 'the client';
        $goals = $this->formatGoals($fna);
        $concern = $fna->riskAssessment?->main_financial_concern;
        $urgency = $fna->riskAssessment?->urgency_level;
        $nextAction = $fna->recommended_next_action;

        if (! $canViewFinancial) {
            $parts = ["Based on the DIME analysis for {$clientName}, a protection gap has been identified."];

            if ($goals !== '') {
                $parts[] = "Stated goals include {$goals}.";
            }

            if ($concern) {
                $parts[] = "Their primary concern is {$concern}.";
            }

            if ($urgency) {
                $parts[] = "Urgency is rated {$urgency} — keep the conversation focused on protection priorities without sharing specific dollar amounts.";
            }

            return implode(' ', $parts);
        }

        $need = (float) ($liveResult['total_dime_need'] ?? $fna->dimeAnalysis?->total_dime_need ?? 0);
        $existing = (float) ($liveResult['existing_life_insurance'] ?? $fna->dimeAnalysis?->existing_life_insurance ?? 0)
            + (float) ($liveResult['liquid_assets_allocated'] ?? $fna->dimeAnalysis?->liquid_assets_allocated ?? 0);

        $parts = [
            "Based on the DIME analysis for {$clientName}, total protection need is {$this->formatCurrency($need)}",
        ];

        if ($existing > 0) {
            $parts[] = "with existing coverage and allocated assets of {$this->formatCurrency($existing)}";
        }

        if ($gap > 0) {
            $parts[] = "leaving an estimated protection gap of {$this->formatCurrency($gap)}";
        } else {
            $parts[] = 'with no material protection gap identified at this time';
        }

        $narrative = rtrim(implode(', ', $parts), ', ').'.';

        if ($goals !== '') {
            $narrative .= " The client identified goals of {$goals}.";
        }

        if ($concern) {
            $narrative .= " Their primary concern is {$concern}.";
        }

        if ($urgency) {
            $narrative .= " Given the {$urgency} urgency level,";
            $narrative .= $nextAction
                ? " the recommended next step is {$nextAction}."
                : ' prioritize a protection-focused follow-up conversation.';
        } elseif ($nextAction) {
            $narrative .= " Recommended next action: {$nextAction}.";
        }

        $fallback = trim($narrative);

        return $this->enhanceWithLlm(
            'You are an FNA coaching assistant. Rewrite the protection gap summary in one concise paragraph. Do not add product recommendations.',
            $fallback,
            $fallback,
        );
    }

    /**
     * @return list<string>
     */
    public function meetingTalkingPoints(FnaRecord $fna, ?User $viewer = null): array
    {
        if (! $this->isEnabled('meeting_prep')) {
            return [];
        }

        $eligibleStatuses = ['approved_by_cfm', 'scheduled_for_client_review', 'presented_to_prospect'];

        if (! in_array($fna->status, $eligibleStatuses, true)) {
            return [];
        }

        $fna->loadMissing(['goals', 'riskAssessment', 'dimeAnalysis']);

        $viewer ??= auth()->user();
        $canViewFinancial = $viewer
            ? Gate::forUser($viewer)->allows('viewFinancialDetails', $fna)
            : true;

        $points = [];
        $goals = $this->formatGoals($fna);

        if ($goals !== '') {
            $points[] = "Open by confirming stated goals: {$goals}.";
        } else {
            $points[] = 'Open by asking which financial goals matter most to the client right now.';
        }

        $concern = $fna->riskAssessment?->main_financial_concern;
        $points[] = $concern
            ? "Address their main concern — {$concern} — and how protection planning supports it."
            : 'Explore their primary financial concern before presenting solutions.';

        if ($canViewFinancial && ($fna->dime_completed || (float) $fna->protection_gap > 0)) {
            $gap = $this->resolveProtectionGap($fna);
            if ($gap > 0) {
                $points[] = "Highlight the estimated protection gap of {$this->formatCurrency($gap)} and the recommended coverage range.";
            } else {
                $points[] = 'Review DIME results together and confirm whether current coverage still meets household needs.';
            }
        } else {
            $points[] = 'Discuss protection needs in qualitative terms and confirm comfort before sharing specific figures.';
        }

        $needs = $fna->main_needs_identified;
        if ($needs) {
            $points[] = "Reinforce identified needs: {$needs}.";
        }

        $nextAction = $fna->recommended_next_action ?: $fna->associate_recommendation;
        $points[] = $nextAction
            ? "Close with the agreed next step: {$nextAction}."
            : 'Close by agreeing on a clear follow-up action and timeline.';

        return $points;
    }

    public function complianceNotice(): string
    {
        return (string) config(
            'fna.ai.compliance_notice',
            'AI-generated suggestions are for coaching and planning support only.',
        );
    }

    public function enhanceWithLlm(string $systemPrompt, string $userContent, string $fallback): string
    {
        if (! config('fna.ai.use_llm', false)) {
            return $fallback;
        }

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');

        if (! $apiKey) {
            return $fallback;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => 300,
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $content = $response->json('choices.0.message.content');

            return is_string($content) && trim($content) !== '' ? trim($content) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function evaluateCondition(string $condition, FnaRecord $fna): bool
    {
        return match ($condition) {
            'dime_not_completed' => ! $fna->dime_completed,
            'no_goals' => count($fna->goals?->selected_goals ?? []) === 0,
            'high_urgency_no_concern' => ($fna->riskAssessment?->urgency_level === 'high')
                && blank($fna->riskAssessment?->main_financial_concern),
            'debt_without_income' => $this->hasDebt($fna) && ! $this->hasIncome($fna),
            'missing_risk_assessment' => blank($fna->riskAssessment?->main_financial_concern)
                || blank($fna->riskAssessment?->urgency_level)
                || blank($fna->riskAssessment?->risk_tolerance),
            'missing_contact_info' => blank($fna->client_email) || blank($fna->client_phone),
            'below_completeness_threshold' => ! $this->completeness->meetsThreshold($fna),
            'missing_coverage_info' => blank($fna->existingCoverage?->existing_life_insurance_amount)
                && blank($fna->existingCoverage?->term_coverage),
            'missing_summary_fields' => blank($fna->main_needs_identified)
                || blank($fna->recommended_next_action),
            default => false,
        };
    }

    private function hasDebt(FnaRecord $fna): bool
    {
        $debt = $fna->debtDetail;

        if (! $debt) {
            return false;
        }

        return (float) ($debt->total_debt ?? 0) > 0
            || (float) ($debt->credit_card_debt ?? 0) > 0
            || (float) ($debt->mortgage_balance ?? 0) > 0;
    }

    private function hasIncome(FnaRecord $fna): bool
    {
        $income = $fna->incomeDetail;

        return (float) ($income?->annual_income ?? 0) > 0
            || (float) ($income?->monthly_income ?? 0) > 0;
    }

    private function resolveProtectionGap(FnaRecord $fna, ?array $liveResult = null): float
    {
        if ($liveResult !== null && isset($liveResult['estimated_protection_gap'])) {
            return (float) $liveResult['estimated_protection_gap'];
        }

        if ($fna->protection_gap !== null) {
            return (float) $fna->protection_gap;
        }

        return (float) ($fna->dimeAnalysis?->estimated_protection_gap ?? 0);
    }

    private function formatGoals(FnaRecord $fna): string
    {
        $selected = $fna->goals?->selected_goals ?? [];
        $labels = collect($selected)
            ->map(fn (string $key): string => config("fna.goal_options.{$key}", str($key)->title()->toString()))
            ->filter()
            ->values();

        return $labels->implode(', ');
    }

    private function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 0);
    }

    private function normalizePriority(string $priority): string
    {
        return match ($priority) {
            'urgent', 'high' => 'high',
            'low' => 'low',
            default => 'medium',
        };
    }

    /**
     * @param  list<array{priority: string, message: string, action: string}>  $items
     * @return list<array{priority: string, message: string, action: string}>
     */
    private function sortByPriority(array $items): array
    {
        $order = ['high' => 1, 'medium' => 2, 'low' => 3];

        usort($items, fn (array $a, array $b): int => ($order[$a['priority']] ?? 4) <=> ($order[$b['priority']] ?? 4));

        return $items;
    }
}
