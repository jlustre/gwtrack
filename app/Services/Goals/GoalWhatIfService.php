<?php

namespace App\Services\Goals;

use App\Models\GoalSimulation;
use App\Models\User;

class GoalWhatIfService
{
    public function __construct(
        private readonly GoalFunnelEngine $funnelEngine,
    ) {}

    /**
     * @param  array{planning_type: string, target_value: float, target_rank?: string}  $inputs
     * @return array{funnel: list<array<string, mixed>>, summary: array<string, float>}
     */
    public function simulate(User $user, array $inputs, bool $persist = false): array
    {
        $planningType = $inputs['planning_type'];
        $targetValue = (float) $inputs['target_value'];
        $funnel = $this->funnelEngine->buildFunnel($user, $planningType, $targetValue, $inputs['target_rank'] ?? null);

        $summary = [
            'daily_contacts' => 0,
            'weekly_fnas' => 0,
            'monthly_presentations' => 0,
            'annual_production' => 0,
            'annual_income' => 0,
            'annual_recruits' => 0,
        ];

        foreach ($funnel as $stage) {
            $key = $stage['key'];

            if (isset($summary[$key])) {
                $summary[$key] = (float) $stage['annual_target'];
            }

            if ($key === 'daily_contacts') {
                $summary['daily_contacts'] = (float) $stage['daily_target'];
            }

            if ($key === 'fnas') {
                $summary['weekly_fnas'] = round(((float) $stage['monthly_target']) / 4.33, 1);
            }

            if ($key === 'presentations') {
                $summary['monthly_presentations'] = (float) $stage['monthly_target'];
            }

            if ($key === 'annual_production') {
                $summary['annual_production'] = (float) $stage['annual_target'];
            }

            if ($key === 'annual_income') {
                $summary['annual_income'] = (float) $stage['annual_target'];
            }

            if ($key === 'annual_recruits') {
                $summary['annual_recruits'] = (float) $stage['annual_target'];
            }
        }

        $results = ['funnel' => $funnel, 'summary' => $summary];

        if ($persist) {
            GoalSimulation::query()->create([
                'user_id' => $user->id,
                'scenario_type' => $planningType,
                'name' => 'What-if: '.$targetValue,
                'inputs' => $inputs,
                'results' => $results,
            ]);
        }

        return $results;
    }
}
