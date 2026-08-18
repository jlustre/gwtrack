<?php

namespace App\Services\Goals;

use App\Models\GoalConversionRate;
use App\Models\User;

class GoalConversionRateService
{
    /**
     * @return array<string, array<string, float>>
     */
    public function ratesForFunnel(User $user, string $funnelKey): array
    {
        $defaults = config("goals-planning.default_conversion_rates.{$funnelKey}", []);

        $overrides = GoalConversionRate::query()
            ->where('funnel_key', $funnelKey)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->get()
            ->groupBy('from_stage');

        $merged = $defaults;

        foreach ($overrides as $fromStage => $rows) {
            foreach ($rows->where('user_id', $user->id) as $row) {
                $merged[$fromStage][$row->to_stage] = (float) $row->rate;
            }

            foreach ($rows->whereNull('user_id') as $row) {
                if (! isset($merged[$fromStage][$row->to_stage])) {
                    $merged[$fromStage][$row->to_stage] = (float) $row->rate;
                }
            }
        }

        return $merged;
    }

    public function rate(User $user, string $funnelKey, string $fromStage, string $toStage): float
    {
        $rates = $this->ratesForFunnel($user, $funnelKey);

        return (float) ($rates[$fromStage][$toStage] ?? 0);
    }

    /**
     * @param  array<string, float>  $rates  Keys like "income.applications.fnas" with percent values (0-100).
     */
    public function upsertUserRates(User $user, array $rates): void
    {
        foreach ($rates as $compoundKey => $percent) {
            if (! is_numeric($percent)) {
                continue;
            }

            [$funnelKey, $fromStage, $toStage] = array_pad(explode('.', (string) $compoundKey, 3), 3, null);

            if (! $funnelKey || ! $fromStage || ! $toStage) {
                continue;
            }

            GoalConversionRate::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'funnel_key' => $funnelKey,
                    'from_stage' => $fromStage,
                    'to_stage' => $toStage,
                ],
                [
                    'rate' => round((float) $percent / 100, 4),
                    'calculated_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<array{from: string, to: string, label: string, rate: float}>
     */
    public function kpiLabelsForFunnel(string $funnelKey, User $user): array
    {
        $labels = [
            'prospect_contacts' => 'invitations',
            'invitations' => 'appointments',
            'appointments' => 'presentations',
            'presentations' => 'fnas',
            'fnas' => 'applications',
            'applications' => 'annual_production',
            'recruiting_contacts' => 'recruiting_invitations',
            'recruiting_invitations' => 'recruiting_appointments',
            'recruiting_appointments' => 'recruiting_presentations',
            'recruiting_presentations' => 'registrations',
            'registrations' => 'monthly_recruits',
        ];

        $result = [];

        foreach ($labels as $from => $to) {
            $rate = $this->rate($user, $funnelKey, $from, $to);

            if ($rate <= 0) {
                continue;
            }

            $result[] = [
                'from' => $from,
                'to' => $to,
                'label' => str_replace('_', ' ', $from).' → '.str_replace('_', ' ', $to),
                'rate' => round($rate * 100, 1),
            ];
        }

        return $result;
    }
}
