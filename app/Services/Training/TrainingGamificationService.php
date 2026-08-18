<?php

namespace App\Services\Training;

use App\Models\AssessmentAttempt;
use App\Models\TrainingBadge;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserTrainingBadge;
use App\Models\UserTrainingCertification;
use App\Models\UserTrainingGamificationProfile;
use App\Models\UserTrainingPathEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingGamificationService
{
    public function __construct(
        private readonly TrainingCoursePlayerService $courses,
    ) {}

    public function profileFor(User $user): UserTrainingGamificationProfile
    {
        return UserTrainingGamificationProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_points' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'lessons_completed_total' => 0,
                'courses_completed_total' => 0,
            ],
        );
    }

    public function recordLessonCompleted(User $user): UserTrainingGamificationProfile
    {
        $profile = $this->profileFor($user);
        $today = now()->toDateString();
        $lastDate = $profile->last_lesson_completed_on?->toDateString();

        $currentStreak = $profile->current_streak;

        if ($lastDate === $today) {
            // Same day — streak unchanged.
        } elseif ($lastDate && now()->parse($lastDate)->addDay()->toDateString() === $today) {
            $currentStreak++;
        } else {
            $currentStreak = 1;
        }

        $profile->update([
            'lessons_completed_total' => $profile->lessons_completed_total + 1,
            'current_streak' => $currentStreak,
            'longest_streak' => max($profile->longest_streak, $currentStreak),
            'last_lesson_completed_on' => $today,
            'total_points' => $profile->total_points + $this->pointsFor('lesson_completed'),
        ]);

        $this->evaluateBadges($user->fresh());

        return $profile->fresh();
    }

    public function recordCourseCompleted(User $user, TrainingModule $module): void
    {
        if ($this->courses->moduleProgressPercent($user, $module) < 100) {
            return;
        }

        $profile = $this->profileFor($user);

        $completedModules = $this->courses->publishedCourses()->filter(
            fn (TrainingModule $published) => $this->courses->moduleProgressPercent($user, $published) >= 100
        )->count();

        if ($completedModules <= $profile->courses_completed_total) {
            $this->evaluateBadges($user, $module);

            return;
        }

        $delta = $completedModules - $profile->courses_completed_total;

        $profile->update([
            'courses_completed_total' => $completedModules,
            'total_points' => $profile->total_points + ($this->pointsFor('course_completed') * $delta),
        ]);

        $this->evaluateBadges($user->fresh(), $module);
    }

    public function recordAssessmentPassed(User $user, AssessmentAttempt $attempt): void
    {
        $profile = $this->profileFor($user);

        $profile->update([
            'total_points' => $profile->total_points + $this->pointsFor('assessment_passed'),
        ]);

        if ($attempt->score >= 100) {
            $this->awardBadge($user, 'assessment-ace');
        }

        $this->evaluateBadges($user->fresh());
    }

    public function recordCertificationIssued(User $user, UserTrainingCertification $record): void
    {
        if ($record->status !== 'issued') {
            return;
        }

        $profile = $this->profileFor($user);

        $profile->update([
            'total_points' => $profile->total_points + $this->pointsFor('certification_issued'),
        ]);

        $code = $record->certification?->code;

        if ($code === 'leadership-certification') {
            $this->awardBadge($user, 'cfm-certified');
        }

        if ($code === 'prospecting-certification') {
            $this->awardBadge($user, 'prospecting-certified');
        }

        $this->evaluateBadges($user->fresh());
    }

    public function recordSessionAttended(User $user): void
    {
        $profile = $this->profileFor($user);

        $profile->update([
            'total_points' => $profile->total_points + $this->pointsFor('session_attended'),
        ]);

        $this->evaluateBadges($user->fresh());
    }

    public function recordPathCompleted(User $user): void
    {
        $profile = $this->profileFor($user);

        $profile->update([
            'total_points' => $profile->total_points + $this->pointsFor('path_completed'),
        ]);

        $this->awardBadge($user, 'path-graduate');
        $this->evaluateBadges($user->fresh());
    }

    public function awardBadge(User $user, string $code): ?UserTrainingBadge
    {
        $badge = TrainingBadge::query()->where('code', $code)->where('is_active', true)->first();

        if (! $badge) {
            return null;
        }

        $record = UserTrainingBadge::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'training_badge_id' => $badge->id,
            ],
            [
                'earned_at' => now(),
            ],
        );

        if ($record->wasRecentlyCreated && $badge->points > 0) {
            $profile = $this->profileFor($user);
            $profile->update([
                'total_points' => $profile->total_points + $badge->points,
            ]);
        }

        return $record;
    }

    public function evaluateBadges(User $user, ?TrainingModule $completedModule = null): void
    {
        $profile = $this->profileFor($user);

        if ($profile->courses_completed_total >= 1) {
            $this->awardBadge($user, 'first-course');
        }

        if ($profile->courses_completed_total >= 3) {
            $this->awardBadge($user, 'dedicated-learner');
        }

        foreach (config('training-academy.gamification.module_badges', []) as $badgeCode => $moduleSlug) {
            if ($completedModule && $completedModule->slug === $moduleSlug) {
                $this->awardBadge($user, $badgeCode);

                continue;
            }

            $module = TrainingModule::query()->published()->where('slug', $moduleSlug)->first();

            if ($module && $this->courses->moduleProgressPercent($user, $module) >= 100) {
                $this->awardBadge($user, $badgeCode);
            }
        }

        foreach (config('training-academy.gamification.streak_badges', []) as $badgeCode => $days) {
            if ($profile->current_streak >= $days || $profile->longest_streak >= $days) {
                $this->awardBadge($user, $badgeCode);
            }
        }

        $hasCompletedPath = UserTrainingPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();

        if ($hasCompletedPath) {
            $this->awardBadge($user, 'path-graduate');
        }
    }

    /**
     * @return array{
     *     profile: UserTrainingGamificationProfile,
     *     badges: Collection<int, UserTrainingBadge>,
     *     available_badges: Collection<int, TrainingBadge>,
     *     leaderboard: list<array{rank: int, user: User, points: int, streak: int, badge_count: int}>,
     *     team_leaderboard: list<array{rank: int, user: User, points: int, streak: int, badge_count: int}>,
     *     rank: int|null
     * }
     */
    public function achievementsHubFor(User $user): array
    {
        $profile = $this->profileFor($user);

        $badges = UserTrainingBadge::query()
            ->with('badge')
            ->where('user_id', $user->id)
            ->orderByDesc('earned_at')
            ->get();

        $earnedIds = $badges->pluck('training_badge_id');

        $availableBadges = TrainingBadge::query()
            ->where('is_active', true)
            ->orderBy('points')
            ->get();

        $leaderboard = $this->leaderboardRows('organization');
        $teamLeaderboard = $user->team_id
            ? $this->leaderboardRows('team', $user)
            : [];

        $rank = collect($leaderboard)->firstWhere('user.id', $user->id)['rank'] ?? null;

        return [
            'profile' => $profile,
            'badges' => $badges,
            'available_badges' => $availableBadges,
            'earned_badge_ids' => $earnedIds,
            'leaderboard' => $leaderboard,
            'team_leaderboard' => $teamLeaderboard,
            'rank' => $rank,
        ];
    }

    /**
     * @return list<array{rank: int, user: User, points: int, streak: int, badge_count: int}>
     */
    public function leaderboardRows(string $scope = 'organization', ?User $contextUser = null, int $limit = 0): array
    {
        $limit = $limit > 0 ? $limit : (int) config('training-academy.gamification.leaderboard_limit', 10);

        $query = UserTrainingGamificationProfile::query()
            ->with('user')
            ->where('total_points', '>', 0)
            ->orderByDesc('total_points')
            ->orderByDesc('longest_streak')
            ->orderBy('user_id');

        if ($scope === 'team' && $contextUser?->team_id) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('team_id', $contextUser->team_id));
        }

        $profiles = $query->limit($limit)->get();

        $badgeCounts = UserTrainingBadge::query()
            ->select('user_id', DB::raw('count(*) as badge_count'))
            ->whereIn('user_id', $profiles->pluck('user_id'))
            ->groupBy('user_id')
            ->pluck('badge_count', 'user_id');

        return $profiles->values()->map(function (UserTrainingGamificationProfile $row, int $index) use ($badgeCounts): array {
            return [
                'rank' => $index + 1,
                'user' => $row->user,
                'points' => $row->total_points,
                'streak' => $row->current_streak,
                'badge_count' => (int) ($badgeCounts[$row->user_id] ?? 0),
            ];
        })->all();
    }

    /**
     * @return array{points: int, streak: int, rank: int|null, badge_count: int}
     */
    public function summaryFor(User $user): array
    {
        $profile = $this->profileFor($user);
        $badgeCount = UserTrainingBadge::query()->where('user_id', $user->id)->count();
        $leaderboard = $this->leaderboardRows('organization', null, 100);
        $rank = collect($leaderboard)->firstWhere('user.id', $user->id)['rank'] ?? null;

        return [
            'points' => $profile->total_points,
            'streak' => $profile->current_streak,
            'longest_streak' => $profile->longest_streak,
            'rank' => $rank,
            'badge_count' => $badgeCount,
        ];
    }

    private function pointsFor(string $event): int
    {
        return (int) config('training-academy.gamification.points.'.$event, 0);
    }
}
