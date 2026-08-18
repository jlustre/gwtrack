<?php

namespace App\Services\Goals;

use App\Models\User;

class GoalFunnelEngine
{
    public function __construct(
        private readonly GoalConversionRateService $conversionRates,
        private readonly GoalPlanningSettingsService $planningSettings,
    ) {}

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     goal_type: string,
     *     metric_key: string|null,
     *     measurement: string,
     *     annual_target: float,
     *     monthly_target: float,
     *     weekly_target: float,
     *     daily_target: float,
     *     hierarchy_level: string,
     * }>
     */
    public function buildFunnel(User $user, string $planningType, float $targetValue, ?string $targetRank = null): array
    {
        $config = config("goals-planning.planning_types.{$planningType}");

        if ($config === null) {
            return [];
        }

        $funnelKey = $config['funnel'];

        return match ($planningType) {
            'income' => $this->buildIncomeFunnel($user, $funnelKey, $targetValue),
            'production' => $this->buildProductionFunnel($user, $funnelKey, $targetValue),
            'recruiting' => $this->buildRecruitingFunnel($user, $funnelKey, $targetValue),
            'rank' => $this->buildRankFunnel($user, $targetRank ?? 'SM'),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildIncomeFunnel(User $user, string $funnelKey, float $annualIncome): array
    {
        $constants = $this->planningSettings->constantsFor($user);
        $commission = (float) ($constants['income_commission_rate'] ?? 0.20);
        $avgPremium = (float) ($constants['avg_annual_premium_per_application'] ?? 2500);
        $workingDays = (int) ($constants['working_days_per_month'] ?? 22);
        $weeksPerMonth = (float) ($constants['weeks_per_month'] ?? 4.33);

        $annualProduction = $annualIncome / max($commission, 0.01);
        $monthlyProduction = $annualProduction / 12;
        $weeklyProduction = $monthlyProduction / max($weeksPerMonth, 0.01);

        $annualApplications = $annualProduction / max($avgPremium, 1);
        $monthlyApplications = $annualApplications / 12;

        $fnas = $this->divide($annualApplications, $this->conversion($user, $funnelKey, 'fnas', 'applications', 0.85));
        $presentations = $this->divide($fnas, $this->conversion($user, $funnelKey, 'presentations', 'fnas', 0.75));
        $appointments = $this->divide($presentations, $this->conversion($user, $funnelKey, 'appointments', 'presentations', 0.60));
        $invitations = $this->divide($appointments, $this->conversion($user, $funnelKey, 'invitations', 'appointments', 0.50));
        $contacts = $this->divide($invitations, $this->conversion($user, $funnelKey, 'prospect_contacts', 'invitations', 0.40));

        $stages = config("goals-planning.funnels.{$funnelKey}", []);

        return $this->mapStages($stages, [
            'annual_income' => $annualIncome,
            'annual_production' => $annualProduction,
            'monthly_production' => $monthlyProduction,
            'weekly_production' => $weeklyProduction,
            'applications' => $annualApplications,
            'fnas' => $fnas,
            'presentations' => $presentations,
            'appointments' => $appointments,
            'invitations' => $invitations,
            'prospect_contacts' => $contacts,
            'daily_contacts' => $contacts / 12 / $workingDays,
        ], null, $user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildProductionFunnel(User $user, string $funnelKey, float $annualProduction): array
    {
        $avgPremium = (float) ($this->planningSettings->constantsFor($user)['avg_annual_premium_per_application'] ?? 2500);

        $monthlyProduction = $annualProduction / 12;
        $annualApplications = $annualProduction / max($avgPremium, 1);
        $fnas = $this->divide($annualApplications, $this->conversion($user, $funnelKey, 'fnas', 'applications', 0.85));
        $presentations = $this->divide($fnas, $this->conversion($user, $funnelKey, 'presentations', 'fnas', 0.75));
        $appointments = $this->divide($presentations, $this->conversion($user, $funnelKey, 'appointments', 'presentations', 0.60));
        $invitations = $this->divide($appointments, $this->conversion($user, $funnelKey, 'invitations', 'appointments', 0.50));
        $contacts = $this->divide($invitations, $this->conversion($user, $funnelKey, 'prospect_contacts', 'invitations', 0.40));

        $stages = config("goals-planning.funnels.{$funnelKey}", []);

        return $this->mapStages($stages, [
            'annual_production' => $annualProduction,
            'monthly_production' => $monthlyProduction,
            'applications' => $annualApplications,
            'fnas' => $fnas,
            'presentations' => $presentations,
            'appointments' => $appointments,
            'invitations' => $invitations,
            'prospect_contacts' => $contacts,
        ], null, $user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRecruitingFunnel(User $user, string $funnelKey, float $annualRecruits): array
    {
        $monthlyRecruits = $annualRecruits / 12;
        $registrations = $this->divide($monthlyRecruits, $this->conversion($user, $funnelKey, 'registrations', 'monthly_recruits', 0.50));
        $presentations = $this->divide($registrations, $this->conversion($user, $funnelKey, 'recruiting_presentations', 'registrations', 0.50));
        $appointments = $this->divide($presentations, $this->conversion($user, $funnelKey, 'recruiting_appointments', 'recruiting_presentations', 0.40));
        $invitations = $this->divide($appointments, $this->conversion($user, $funnelKey, 'recruiting_invitations', 'recruiting_appointments', 0.40));
        $contacts = $this->divide($invitations, $this->conversion($user, $funnelKey, 'recruiting_contacts', 'recruiting_invitations', 0.50));

        $stages = config("goals-planning.funnels.{$funnelKey}", []);

        return $this->mapStages($stages, [
            'annual_recruits' => $annualRecruits,
            'monthly_recruits' => $monthlyRecruits,
            'registrations' => $registrations,
            'recruiting_presentations' => $presentations,
            'recruiting_appointments' => $appointments,
            'recruiting_invitations' => $invitations,
            'recruiting_contacts' => $contacts,
        ], null, $user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRankFunnel(User $user, string $targetRank): array
    {
        $requirements = config('goals-planning.rank_requirements.'.$targetRank, [
            'production' => 100000,
            'recruits' => 3,
            'licensing' => 100,
            'fap' => 100,
            'training' => 100,
        ]);

        $stages = config('goals-planning.funnels.rank', []);

        return $this->mapStages($stages, [
            'target_rank' => 100,
            'rank_production' => (float) $requirements['production'],
            'rank_recruits' => (float) $requirements['recruits'],
            'rank_licensing' => (float) $requirements['licensing'],
            'rank_fap' => (float) $requirements['fap'],
            'rank_training' => (float) $requirements['training'],
        ], $targetRank, $user);
    }

    /**
     * @param  list<array<string, mixed>>  $stages
     * @param  array<string, float>  $values
     * @return list<array<string, mixed>>
     */
    private function mapStages(array $stages, array $values, ?string $rankLabel = null, ?User $user = null): array
    {
        $constants = $user ? $this->planningSettings->constantsFor($user) : config('goals-planning.planning_constants', []);
        $workingDays = max((int) ($constants['working_days_per_month'] ?? 22), 1);
        $weeksPerMonth = max((float) ($constants['weeks_per_month'] ?? 4.33), 0.01);

        return collect($stages)
            ->map(function (array $stage) use ($values, $rankLabel, $workingDays, $weeksPerMonth): array {
                $annual = (float) ($values[$stage['key']] ?? 0);
                $monthly = $this->monthlyEquivalent($stage['key'], $annual);
                $weekly = $monthly / $weeksPerMonth;
                $daily = $monthly / $workingDays;

                return [
                    ...$stage,
                    'annual_target' => round($annual, 2),
                    'monthly_target' => round($monthly, 2),
                    'weekly_target' => round($weekly, 2),
                    'daily_target' => round($daily, 2),
                    'hierarchy_level' => $this->hierarchyForStage($stage['key']),
                    'display_target' => $stage['key'] === 'target_rank' ? $rankLabel : round($annual, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function monthlyEquivalent(string $stageKey, float $annual): float
    {
        if (str_contains($stageKey, 'monthly') || str_contains($stageKey, 'daily')) {
            return $annual;
        }

        if (str_contains($stageKey, 'weekly')) {
            return $annual * 4.33;
        }

        if (in_array($stageKey, ['target_rank', 'rank_licensing', 'rank_fap', 'rank_training'], true)) {
            return $annual;
        }

        return $annual / 12;
    }

    private function hierarchyForStage(string $stageKey): string
    {
        return match (true) {
            str_contains($stageKey, 'daily') => 'daily',
            str_contains($stageKey, 'weekly') => 'weekly',
            str_contains($stageKey, 'monthly') => 'monthly',
            str_contains($stageKey, 'annual') || str_contains($stageKey, 'rank_') => 'annual',
            default => 'monthly',
        };
    }

    private function conversion(User $user, string $funnelKey, string $from, string $to, float $fallback): float
    {
        $rate = $this->conversionRates->rate($user, $funnelKey, $from, $to);

        return $rate > 0 ? $rate : $fallback;
    }

    private function divide(float $value, float $rate): float
    {
        return $rate > 0 ? $value / $rate : 0;
    }
}
