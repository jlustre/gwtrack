<?php

namespace App\Services\Goals;

use App\Models\GoalPlanningSetting;
use App\Models\User;
use Illuminate\Support\Arr;

class GoalPlanningSettingsService
{
    /**
     * @return array<string, float|int>
     */
    public function constantsFor(User $user): array
    {
        $defaults = config('goals-planning.planning_constants', []);
        $overrides = GoalPlanningSetting::query()
            ->where('user_id', $user->id)
            ->value('constants') ?? [];

        return array_merge($defaults, Arr::only($overrides, array_keys($defaults)));
    }

    /**
     * @return array<string, mixed>
     */
    public function formStateFor(User $user): array
    {
        $constants = $this->constantsFor($user);
        $conversionRates = app(GoalConversionRateService::class);

        $rates = [];

        foreach (config('goals-planning.editable_conversion_rates', []) as $funnelKey => $stages) {
            foreach ($stages as $stage) {
                $key = "{$funnelKey}.{$stage['from']}.{$stage['to']}";
                $rate = $conversionRates->rate($user, $funnelKey, $stage['from'], $stage['to']);
                $defaults = config("goals-planning.default_conversion_rates.{$funnelKey}", []);
                $defaultRate = (float) ($defaults[$stage['from']][$stage['to']] ?? 0);
                $rates[$key] = $rate > 0 ? round($rate * 100, 1) : round($defaultRate * 100, 1);
            }
        }

        return [
            'income_commission_percent' => round((float) $constants['income_commission_rate'] * 100, 1),
            'avg_annual_premium_per_application' => (float) $constants['avg_annual_premium_per_application'],
            'working_days_per_month' => (int) $constants['working_days_per_month'],
            'working_weeks_per_year' => (int) $constants['working_weeks_per_year'],
            'weeks_per_month' => (float) $constants['weeks_per_month'],
            'conversion_rates' => $rates,
            'has_custom_settings' => GoalPlanningSetting::query()->where('user_id', $user->id)->exists()
                || $this->userHasCustomConversionRates($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function save(User $user, array $validated): void
    {
        GoalPlanningSetting::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'constants' => [
                    'income_commission_rate' => round((float) $validated['income_commission_percent'] / 100, 4),
                    'avg_annual_premium_per_application' => (float) $validated['avg_annual_premium_per_application'],
                    'working_days_per_month' => (int) $validated['working_days_per_month'],
                    'working_weeks_per_year' => (int) $validated['working_weeks_per_year'],
                    'weeks_per_month' => (float) $validated['weeks_per_month'],
                ],
            ],
        );

        if (! empty($validated['conversion_rates']) && is_array($validated['conversion_rates'])) {
            app(GoalConversionRateService::class)->upsertUserRates($user, $validated['conversion_rates']);
        }
    }

    public function resetToDefaults(User $user): void
    {
        GoalPlanningSetting::query()->where('user_id', $user->id)->delete();

        \App\Models\GoalConversionRate::query()
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * @return array{income: float, production: float, applications: float}
     */
    public function previewForIncomeTarget(User $user, float $annualIncome): array
    {
        $constants = $this->constantsFor($user);
        $commission = max((float) $constants['income_commission_rate'], 0.01);
        $avgPremium = max((float) $constants['avg_annual_premium_per_application'], 1);
        $production = $annualIncome / $commission;
        $applications = $production / $avgPremium;

        return [
            'income' => round($annualIncome, 0),
            'production' => round($production, 0),
            'applications' => round($applications, 1),
            'commission_percent' => round($commission * 100, 1),
        ];
    }

    private function userHasCustomConversionRates(User $user): bool
    {
        return \App\Models\GoalConversionRate::query()
            ->where('user_id', $user->id)
            ->exists();
    }
}
