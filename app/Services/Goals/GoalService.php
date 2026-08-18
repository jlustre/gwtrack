<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalCoach;
use App\Models\GoalMilestone;
use App\Models\GoalReminder;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function __construct(
        private readonly SmartGoalValidator $smartValidator,
        private readonly GoalMetricResolver $metricResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{name: string, due_at?: string|null, target_value?: float|null}>  $milestones
     * @param  list<array{coach_user_id: int, role: string}>  $coaches
     */
    public function create(User $owner, array $data, array $milestones = [], array $coaches = []): Goal
    {
        $smart = $this->smartValidator->evaluate($data);

        return DB::transaction(function () use ($owner, $data, $milestones, $coaches, $smart): Goal {
            $goal = Goal::query()->create([
                'user_id' => $owner->id,
                'created_by_user_id' => auth()->id(),
                'goal_category_id' => $data['goal_category_id'],
                'parent_goal_id' => $data['parent_goal_id'] ?? null,
                'goal_template_id' => $data['goal_template_id'] ?? null,
                'hierarchy_level' => $data['hierarchy_level'] ?? 'monthly',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'measurement_type' => $data['measurement_type'] ?? 'number',
                'metric_key' => $data['metric_key'] ?? null,
                'target_value' => $data['target_value'] ?? 0,
                'currency_code' => $data['currency_code'] ?? ($data['measurement_type'] === 'currency' ? 'CAD' : null),
                'status' => $data['status'] ?? 'active',
                'smart_score' => $smart['score'],
                'smart_feedback' => $smart['feedback'],
                'starts_at' => $data['starts_at'] ?? now()->toDateString(),
                'deadline_at' => $data['deadline_at'] ?? null,
                'accountability_partner_id' => $data['accountability_partner_id'] ?? null,
                'notification_settings' => $data['notification_settings'] ?? ['email' => true, 'in_app' => true],
            ]);

            $this->syncMilestones($goal, $milestones);
            $this->syncCoaches($goal, $coaches);
            $this->syncReminders($goal, $data);

            if (filled($goal->metric_key)) {
                $this->metricResolver->refreshGoal($goal);
            }

            return $goal->fresh(['category', 'milestones', 'coaches']);
        });
    }

    public function update(Goal $goal, array $data, array $milestones = [], array $coaches = []): Goal
    {
        $smart = $this->smartValidator->evaluate(array_merge($goal->only([
            'name', 'description', 'target_value', 'measurement_type', 'deadline_at', 'starts_at', 'metric_key', 'goal_category_id',
        ]), $data));

        return DB::transaction(function () use ($goal, $data, $milestones, $coaches, $smart): Goal {
            $payload = [
                ...Arr::only($data, [
                    'goal_category_id', 'parent_goal_id', 'hierarchy_level', 'name', 'description',
                    'measurement_type', 'metric_key', 'target_value', 'currency_code', 'status',
                    'starts_at', 'deadline_at', 'accountability_partner_id', 'notification_settings',
                ]),
                'smart_score' => $smart['score'],
                'smart_feedback' => $smart['feedback'],
            ];

            if (array_key_exists('actual_value', $data) && ! filled($goal->metric_key)) {
                $actual = (float) $data['actual_value'];
                $target = (float) ($data['target_value'] ?? $goal->target_value);
                $payload['actual_value'] = $actual;

                if (! array_key_exists('status', $data)) {
                    $payload['status'] = $target > 0 && $actual >= $target ? 'completed' : $goal->status;
                    $payload['completed_at'] = $target > 0 && $actual >= $target ? now() : null;
                }
            }

            $goal->update($payload);

            if ($milestones !== []) {
                $this->syncMilestones($goal, $milestones, replace: true);
            }

            if ($coaches !== []) {
                $this->syncCoaches($goal, $coaches, replace: true);
            }

            if (filled($goal->metric_key)) {
                $this->metricResolver->refreshGoal($goal->fresh());
            }

            return $goal->fresh(['category', 'milestones', 'coaches']);
        });
    }

    public function delete(Goal $goal): void
    {
        DB::transaction(function () use ($goal): void {
            $goal->reminders()->delete();
            $goal->delete();
        });
    }

    public function recordManualProgress(Goal $goal, float $value, ?string $notes = null): Goal
    {
        $goal->update([
            'actual_value' => $value,
            'status' => $goal->target_value > 0 && $value >= (float) $goal->target_value ? 'completed' : $goal->status,
            'completed_at' => $goal->target_value > 0 && $value >= (float) $goal->target_value ? now() : null,
        ]);

        $goal->progressEntries()->create([
            'recorded_at' => now(),
            'value' => $value,
            'source' => 'manual',
            'notes' => $notes,
            'recorded_by_user_id' => auth()->id(),
        ]);

        return $goal->fresh();
    }

    /**
     * @param  list<array{name: string, due_at?: string|null, target_value?: float|null}>  $milestones
     */
    private function syncMilestones(Goal $goal, array $milestones, bool $replace = false): void
    {
        if ($replace) {
            $goal->milestones()->delete();
        }

        foreach ($milestones as $index => $milestone) {
            if (! filled($milestone['name'] ?? null)) {
                continue;
            }

            GoalMilestone::query()->create([
                'goal_id' => $goal->id,
                'name' => $milestone['name'],
                'target_value' => $milestone['target_value'] ?? null,
                'due_at' => $milestone['due_at'] ?? null,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    /**
     * @param  list<array{coach_user_id: int, role: string}>  $coaches
     */
    private function syncCoaches(Goal $goal, array $coaches, bool $replace = false): void
    {
        if ($replace) {
            $goal->coaches()->delete();
        }

        foreach ($coaches as $coach) {
            if (empty($coach['coach_user_id'])) {
                continue;
            }

            GoalCoach::query()->updateOrCreate(
                [
                    'goal_id' => $goal->id,
                    'coach_user_id' => $coach['coach_user_id'],
                    'role' => $coach['role'] ?? 'mentor',
                ],
                [
                    'can_edit' => (bool) ($coach['can_edit'] ?? false),
                    'receives_alerts' => (bool) ($coach['receives_alerts'] ?? true),
                ],
            );
        }

        if (filled($goal->accountability_partner_id)) {
            GoalCoach::query()->updateOrCreate(
                [
                    'goal_id' => $goal->id,
                    'coach_user_id' => $goal->accountability_partner_id,
                    'role' => 'mentor',
                ],
                ['receives_alerts' => true],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncReminders(Goal $goal, array $data): void
    {
        $settings = $data['notification_settings'] ?? [];

        if (! ($settings['remind_weekly'] ?? false) || ! $goal->deadline_at) {
            return;
        }

        GoalReminder::query()->create([
            'goal_id' => $goal->id,
            'user_id' => $goal->user_id,
            'remind_at' => now()->addWeek(),
            'channel' => 'in_app',
            'message' => "Weekly check-in for goal: {$goal->name}",
            'is_active' => true,
        ]);
    }
}
