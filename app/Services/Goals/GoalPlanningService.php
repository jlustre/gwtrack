<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalActivityTarget;
use App\Models\GoalBlueprint;
use App\Models\GoalCategory;
use App\Models\GoalDependency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GoalPlanningService
{
    public function __construct(
        private readonly GoalFunnelEngine $funnelEngine,
        private readonly GoalConversionRateService $conversionRates,
        private readonly SmartGoalValidator $smartValidator,
        private readonly GoalMetricResolver $metricResolver,
    ) {}

    /**
     * @param  array{target_rank?: string, starts_at?: string, deadline_at?: string, name?: string}  $options
     */
    public function createBlueprint(User $user, string $planningType, float $targetValue, array $options = []): GoalBlueprint
    {
        $planConfig = config("goals-planning.planning_types.{$planningType}");
        $funnelKey = $planConfig['funnel'] ?? $planningType;
        $funnel = $this->funnelEngine->buildFunnel($user, $planningType, $targetValue, $options['target_rank'] ?? null);
        $startsAt = $options['starts_at'] ?? now()->toDateString();
        $deadlineAt = $options['deadline_at'] ?? now()->endOfYear()->toDateString();
        $name = $options['name'] ?? $planConfig['label'].' — '.now()->year;

        return DB::transaction(function () use ($user, $planningType, $targetValue, $funnel, $funnelKey, $startsAt, $deadlineAt, $name, $planConfig): GoalBlueprint {
            $category = GoalCategory::query()->where('slug', $planConfig['category_slug'])->first();

            $blueprint = GoalBlueprint::query()->create([
                'user_id' => $user->id,
                'planning_type' => $planningType,
                'name' => $name,
                'period_type' => 'annual',
                'root_target_value' => $targetValue,
                'status' => 'active',
                'funnel_snapshot' => $funnel,
                'conversion_snapshot' => $this->conversionRates->ratesForFunnel($user, $funnelKey),
                'starts_at' => $startsAt,
                'deadline_at' => $deadlineAt,
            ]);

            $createdGoals = [];
            $previousGoalId = null;

            foreach ($funnel as $index => $stage) {
                $target = (float) ($stage['annual_target'] ?? 0);
                $smart = $this->smartValidator->evaluate([
                    'name' => $stage['label'],
                    'description' => "Activity target for {$blueprint->name}",
                    'target_value' => $target,
                    'measurement_type' => $stage['measurement'] === 'currency' ? 'currency' : ($stage['measurement'] === 'percentage' ? 'percentage' : 'number'),
                    'deadline_at' => $deadlineAt,
                    'starts_at' => $startsAt,
                    'metric_key' => $stage['metric_key'] ?? null,
                    'goal_category_id' => $category?->id,
                ]);

                $goal = Goal::query()->create([
                    'user_id' => $user->id,
                    'goal_category_id' => $category?->id,
                    'parent_goal_id' => $previousGoalId,
                    'blueprint_id' => $blueprint->id,
                    'hierarchy_level' => $stage['hierarchy_level'] ?? 'monthly',
                    'goal_type' => $stage['goal_type'] ?? 'outcome',
                    'planning_type' => $planningType,
                    'funnel_stage_key' => $stage['key'],
                    'name' => $stage['label'],
                    'description' => "Planned {$stage['label']} supporting {$name}",
                    'measurement_type' => $stage['measurement'] === 'currency' ? 'currency' : ($stage['measurement'] === 'percentage' ? 'percentage' : 'number'),
                    'metric_key' => $stage['metric_key'] ?? null,
                    'target_value' => $target,
                    'currency_code' => $stage['measurement'] === 'currency' ? 'CAD' : null,
                    'status' => 'active',
                    'smart_score' => $smart['score'],
                    'smart_feedback' => $smart['feedback'],
                    'starts_at' => $startsAt,
                    'deadline_at' => $deadlineAt,
                    'contribution_weight' => 100,
                ]);

                if ($previousGoalId) {
                    GoalDependency::query()->create([
                        'parent_goal_id' => $goal->id,
                        'child_goal_id' => $previousGoalId,
                        'relationship_type' => 'requires',
                        'contribution_percent' => 100,
                        'sort_order' => ($index + 1) * 10,
                    ]);
                }

                foreach (['daily', 'weekly', 'monthly'] as $period) {
                    $periodTarget = (float) ($stage["{$period}_target"] ?? 0);

                    if ($periodTarget <= 0) {
                        continue;
                    }

                    GoalActivityTarget::query()->create([
                        'goal_id' => $goal->id,
                        'activity_key' => $stage['key'],
                        'period_type' => $period,
                        'target_value' => $periodTarget,
                        'period_start' => $startsAt,
                        'period_end' => $deadlineAt,
                    ]);
                }

                if (filled($goal->metric_key)) {
                    $this->metricResolver->refreshGoal($goal);
                }

                $createdGoals[$stage['key']] = $goal;
                $previousGoalId = $goal->id;
            }

            $rootStage = config("goals-planning.planning_types.{$planningType}.root_stage");
            $rootGoal = $createdGoals[$rootStage] ?? reset($createdGoals) ?: null;

            if ($rootGoal) {
                $blueprint->update(['root_goal_id' => $rootGoal->id]);
            }

            return $blueprint->fresh(['goals', 'rootGoal']);
        });
    }
}
