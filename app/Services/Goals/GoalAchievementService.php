<?php

namespace App\Services\Goals;

use App\Models\GoalAchievement;
use App\Models\GoalBadge;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Carbon\Carbon;

class GoalAchievementService
{
    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
        private readonly GoalProductionService $production,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function evaluateForUser(User $user): void
    {
        foreach (config('goals.badge_criteria', []) as $slug => $criteria) {
            $badge = GoalBadge::query()->where('slug', $slug)->where('is_active', true)->first();

            if (! $badge || $this->hasAchievement($user, $badge->id)) {
                continue;
            }

            if ($this->criteriaMet($user, $criteria)) {
                $this->award($user, $badge, $criteria);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function criteriaMet(User $user, array $criteria): bool
    {
        $start = isset($criteria['period_start']) ? Carbon::parse($criteria['period_start']) : now()->startOfYear();
        $end = isset($criteria['period_end']) ? Carbon::parse($criteria['period_end']) : now();

        $value = match ($criteria['type'] ?? 'metric') {
            'production_entries' => $this->production->entryCountForUser($user, $start, $end),
            'metric' => $this->metricResolver->resolve($user, $criteria['metric'], $start, $end),
            default => 0,
        };

        $min = (float) ($criteria['min'] ?? 1);

        return $value >= $min;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function award(User $user, GoalBadge $badge, array $criteria): void
    {
        GoalAchievement::query()->create([
            'user_id' => $user->id,
            'goal_badge_id' => $badge->id,
            'earned_at' => now(),
            'metadata' => ['criteria' => $criteria],
        ]);

        $this->notifications->dispatch('goal_achievement', [
            'queue' => true,
            'recipients' => [$user->id],
            'module' => 'goal',
            'priority' => 'medium',
            'title' => 'Achievement unlocked: '.$badge->name,
            'message' => $badge->description,
            'action_link' => [
                'route' => 'goals.index',
                'params' => [],
                'label' => 'View goals',
            ],
            'payload' => [
                'badge_slug' => $badge->slug,
                'badge_level' => $badge->level,
            ],
        ]);
    }

    private function hasAchievement(User $user, int $badgeId): bool
    {
        return GoalAchievement::query()
            ->where('user_id', $user->id)
            ->where('goal_badge_id', $badgeId)
            ->exists();
    }
}
