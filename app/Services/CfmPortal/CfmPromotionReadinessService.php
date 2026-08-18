<?php

namespace App\Services\CfmPortal;

use App\Models\CfmPromotion;
use App\Models\MentorAssignment;
use App\Models\Rank;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmMilestoneReviewTriggerService;
use App\Services\DownlineHierarchyService;
use Illuminate\Support\Facades\DB;

class CfmPromotionReadinessService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
        private readonly CfmPortalDashboardService $dashboard,
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, bool $refresh = false): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $trainee->loadMissing('rank');
        $record = $refresh
            ? $this->syncForTrainee($cfm, $trainee)
            : $this->latestOrSync($cfm, $trainee);

        $snapshot = $this->buildSnapshot($trainee, $record);

        return [
            'key' => 'promotion',
            'title' => 'Promotion Readiness',
            'description' => 'Track rank advancement requirements, readiness percentage, and promotion milestones.',
            'stats' => [
                'readiness_percent' => $record->readiness_percent,
                'requirements_met' => count($record->requirements_met ?? []),
                'requirements_remaining' => count($record->requirements_remaining ?? []),
                'status' => $record->status,
            ],
            'promotion' => $snapshot,
            'statuses' => CfmPromotion::STATUSES,
            'rank_advancement_url' => route('rank-advancement.index'),
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    public function syncForTrainee(User $cfm, User $trainee): CfmPromotion
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }

        $trainee->loadMissing('rank');
        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        $progress = $this->hierarchy->progressSummary($trainee);
        $rankData = $this->nextRankRequirements($trainee);

        $requirements = [
            [
                'key' => 'onboarding',
                'label' => 'Onboarding complete (90%+)',
                'target' => 90,
                'current' => $progress['onboarding']['percent'] ?? 0,
                'met' => ($progress['onboarding']['percent'] ?? 0) >= 90,
            ],
            [
                'key' => 'fap',
                'label' => 'FAP complete (85%+)',
                'target' => 85,
                'current' => $progress['apprenticeship']['percent'] ?? 0,
                'met' => ($progress['apprenticeship']['percent'] ?? 0) >= 85,
            ],
            [
                'key' => 'licensing',
                'label' => 'Licensing complete (85%+)',
                'target' => 85,
                'current' => $progress['licensing']['percent'] ?? 0,
                'met' => ($progress['licensing']['percent'] ?? 0) >= 85,
            ],
            [
                'key' => 'training',
                'label' => 'Training complete (50%+)',
                'target' => 50,
                'current' => $progress['training']['percent'] ?? 0,
                'met' => ($progress['training']['percent'] ?? 0) >= 50,
            ],
            [
                'key' => 'rank',
                'label' => 'Next rank requirements ('.($rankData['percent'] ?? 0).'%)',
                'target' => 100,
                'current' => $rankData['percent'] ?? 0,
                'met' => ($rankData['percent'] ?? 0) >= 100,
            ],
        ];

        $met = collect($requirements)->filter(fn (array $row) => $row['met'])->values();
        $remaining = collect($requirements)->reject(fn (array $row) => $row['met'])->values();

        $readinessPercent = (int) round(collect($requirements)->avg(function (array $row): float {
            $target = max(1, $row['target']);

            return min(100, ($row['current'] / $target) * 100);
        }));

        $status = 'tracking';
        if ($readinessPercent >= 95 && $remaining->isEmpty()) {
            $status = 'ready';
        } elseif ($rosterRow['is_promotion_ready'] ?? false) {
            $status = 'ready';
        }

        return CfmPromotion::query()->updateOrCreate(
            [
                'cfm_id' => $cfm->id,
                'trainee_id' => $trainee->id,
            ],
            [
                'current_rank_id' => $trainee->rank_id,
                'target_rank_id' => $rankData['next_rank_id'],
                'readiness_percent' => $readinessPercent,
                'requirements_met' => $met->map(fn (array $row) => [
                    'label' => $row['label'],
                    'current' => $row['current'],
                ])->all(),
                'requirements_remaining' => $remaining->map(fn (array $row) => [
                    'label' => $row['label'],
                    'current' => $row['current'],
                    'target' => $row['target'],
                ])->all(),
                'status' => $this->preserveNominatedStatus($cfm, $trainee, $status),
            ],
        );
    }

    public function updateStatus(User $cfm, CfmPromotion $promotion, string $status): CfmPromotion
    {
        if ((int) $promotion->cfm_id !== (int) $cfm->id) {
            abort(403);
        }

        if (! in_array($status, CfmPromotion::STATUSES, true)) {
            abort(422, 'Invalid promotion status.');
        }

        $traineeId = (int) $promotion->trainee_id;

        $promotion->update(['status' => $status]);

        if ($status === 'nominated') {
            $assignment = MentorAssignment::query()
                ->where('mentor_id', $cfm->id)
                ->where('apprentice_id', $traineeId)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($assignment) {
                app(CfmMilestoneReviewTriggerService::class)->onPromotionNominated($assignment);
            }
        }

        return $promotion->refresh();
    }

    public function findForCfm(User $cfm, int $traineeId): CfmPromotion
    {
        return CfmPromotion::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $traineeId)
            ->firstOrFail();
    }

    private function latestOrSync(User $cfm, User $trainee): CfmPromotion
    {
        $existing = CfmPromotion::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->first();

        if ($existing && $existing->updated_at?->gte(now()->subDay())) {
            return $existing->load(['currentRank', 'targetRank']);
        }

        return $this->syncForTrainee($cfm, $trainee)->load(['currentRank', 'targetRank']);
    }

    /**
     * @return array{next_rank_id: int|null, percent: int, requirements: list<array{title: string, status: string}>}
     */
    private function nextRankRequirements(User $trainee): array
    {
        $currentRank = $trainee->rank;

        if (! $currentRank) {
            return ['next_rank_id' => null, 'percent' => 0, 'requirements' => []];
        }

        $nextRank = Rank::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $currentRank->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (! $nextRank) {
            return ['next_rank_id' => null, 'percent' => 100, 'requirements' => []];
        }

        $requirements = DB::table('rank_requirements')
            ->where('rank_id', $nextRank->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        $progress = DB::table('user_rank_progress')
            ->where('user_id', $trainee->id)
            ->whereIn('rank_requirement_id', $requirements->pluck('id'))
            ->get(['rank_requirement_id', 'status'])
            ->keyBy('rank_requirement_id');

        $completed = $requirements->filter(
            fn (object $requirement): bool => ($progress->get($requirement->id)?->status ?? 'not_started') === 'completed'
        )->count();

        $total = $requirements->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'next_rank_id' => $nextRank->id,
            'percent' => $percent,
            'requirements' => $requirements->map(fn (object $requirement): array => [
                'title' => $requirement->title,
                'status' => $progress->get($requirement->id)?->status ?? 'not_started',
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(User $trainee, CfmPromotion $record): array
    {
        $rankData = $this->nextRankRequirements($trainee);

        return [
            'trainee_name' => $trainee->name,
            'current_rank' => $record->currentRank?->name ?? $trainee->rank?->name ?? '—',
            'target_rank' => $record->targetRank?->name ?? '—',
            'readiness_percent' => $record->readiness_percent,
            'status' => $record->status,
            'requirements_met' => $record->requirements_met ?? [],
            'requirements_remaining' => $record->requirements_remaining ?? [],
            'rank_requirements' => $rankData['requirements'],
            'updated_at' => $record->updated_at?->format('M j, Y g:i A'),
        ];
    }

    private function preserveNominatedStatus(User $cfm, User $trainee, string $computedStatus): string
    {
        $existing = CfmPromotion::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->value('status');

        return $existing === 'nominated' ? 'nominated' : $computedStatus;
    }
}
