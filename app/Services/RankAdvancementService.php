<?php

namespace App\Services;

use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\User;
use App\Models\UserRankProgress;
use Illuminate\Support\Collection;

class RankAdvancementService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function resolveMember(User $viewer, ?int $memberId = null): User
    {
        if ($memberId === null || $memberId === $viewer->id) {
            return $viewer->loadMissing(['rank', 'sponsor', 'mentor', 'profile']);
        }

        $member = User::query()
            ->with(['rank', 'sponsor', 'mentor', 'profile'])
            ->findOrFail($memberId);

        abort_unless($this->canViewMember($viewer, $member), 403);

        return $member;
    }

    public function canViewMember(User $viewer, User $member): bool
    {
        if ($viewer->id === $member->id) {
            return true;
        }

        if ($viewer->can('manage rank advancement') || $viewer->can('view rank summary')) {
            return $this->hierarchy->canViewMember($viewer, $member);
        }

        return false;
    }

    public function canReviewMember(User $viewer, User $member): bool
    {
        return $viewer->can('manage rank advancement')
            && $this->hierarchy->canViewMember($viewer, $member);
    }

    public function ensureProgressRecords(User $member): void
    {
        $nextRank = $this->nextRankFor($member);

        if ($nextRank === null) {
            return;
        }

        $requirementIds = RankRequirement::query()
            ->where('rank_id', $nextRank->id)
            ->pluck('id');

        foreach ($requirementIds as $requirementId) {
            UserRankProgress::query()->firstOrCreate(
                [
                    'user_id' => $member->id,
                    'rank_requirement_id' => $requirementId,
                ],
                [
                    'status' => 'not_started',
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function trackerFor(User $viewer, User $member): array
    {
        $this->ensureProgressRecords($member);

        $currentRank = $member->rank;
        $nextRank = $this->nextRankFor($member);
        $requirements = $this->requirementsWithProgress($member, $nextRank);
        $percent = $this->percentComplete($requirements);
        $stats = $this->statsFor($requirements);

        return [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'rank' => $currentRank?->name ?? 'Unassigned',
                'rank_code' => $currentRank?->code,
                'sponsor' => $member->sponsor?->name ?? 'Not assigned',
                'mentor' => $member->mentor?->name ?? 'Pending',
                'country' => $member->profile?->country ?? 'Global',
                'joined_at' => ($member->joined_at ?? $member->created_at)?->format('M j, Y') ?? '—',
            ],
            'current_rank' => $currentRank ? [
                'id' => $currentRank->id,
                'code' => $currentRank->code,
                'name' => $currentRank->name,
            ] : null,
            'next_rank' => $nextRank ? [
                'id' => $nextRank->id,
                'code' => $nextRank->code,
                'name' => $nextRank->name,
            ] : null,
            'percent' => $percent,
            'stats' => $stats,
            'rank_ladder' => $this->rankLadderFor($currentRank),
            'requirement_groups' => $this->groupRequirements($requirements),
            'review_queue' => $requirements
                ->filter(fn (array $row) => in_array($row['status'], config('rank-advancement.review_queue', []), true))
                ->values()
                ->all(),
            'is_self' => $viewer->id === $member->id,
            'can_review' => $this->canReviewMember($viewer, $member),
            'at_max_rank' => $nextRank === null,
        ];
    }

    public function startRequirement(User $member, int $progressId): UserRankProgress
    {
        $progress = $this->progressForMember($member, $progressId);

        abort_unless(in_array($progress->status, config('rank-advancement.member_actionable', []), true), 422);

        $progress->update(['status' => 'in_progress']);

        return $progress->fresh(['requirement']);
    }

    public function submitRequirement(User $member, int $progressId, ?string $notes = null): UserRankProgress
    {
        $progress = $this->progressForMember($member, $progressId);

        abort_unless(in_array($progress->status, ['in_progress', 'rejected'], true), 422);

        $progress->update([
            'status' => 'ready_for_review',
            'member_notes' => $notes ?: $progress->member_notes,
            'submitted_at' => now(),
        ]);

        return $progress->fresh(['requirement']);
    }

    public function approveRequirement(User $reviewer, User $member, int $progressId, ?string $notes = null): UserRankProgress
    {
        abort_unless($this->canReviewMember($reviewer, $member), 403);

        $progress = $this->progressForMember($member, $progressId);

        abort_unless(in_array($progress->status, config('rank-advancement.review_queue', []), true), 422);

        $progress->update([
            'status' => 'completed',
            'reviewer_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => now(),
        ]);

        return $progress->fresh(['requirement']);
    }

    public function rejectRequirement(User $reviewer, User $member, int $progressId, ?string $notes = null): UserRankProgress
    {
        abort_unless($this->canReviewMember($reviewer, $member), 403);

        $progress = $this->progressForMember($member, $progressId);

        abort_unless(in_array($progress->status, config('rank-advancement.review_queue', []), true), 422);

        $progress->update([
            'status' => 'rejected',
            'reviewer_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'completed_at' => null,
        ]);

        return $progress->fresh(['requirement']);
    }

    public function nextRankFor(User $member): ?Rank
    {
        $currentRank = $member->rank;

        if (! $currentRank) {
            return Rank::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();
        }

        return Rank::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $currentRank->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function requirementsWithProgress(User $member, ?Rank $nextRank): Collection
    {
        if ($nextRank === null) {
            return collect();
        }

        $requirements = RankRequirement::query()
            ->where('rank_id', $nextRank->id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $progressByRequirement = UserRankProgress::query()
            ->where('user_id', $member->id)
            ->whereIn('rank_requirement_id', $requirements->pluck('id'))
            ->get()
            ->keyBy('rank_requirement_id');

        return $requirements->map(function (RankRequirement $requirement) use ($progressByRequirement): array {
            $progress = $progressByRequirement->get($requirement->id);

            return [
                'id' => $progress?->id,
                'requirement_id' => $requirement->id,
                'title' => $requirement->title,
                'description' => $requirement->description,
                'category' => $requirement->category,
                'category_label' => $requirement->categoryLabel(),
                'is_required' => $requirement->is_required,
                'status' => $progress?->status ?? 'not_started',
                'status_label' => config('rank-advancement.statuses.'.($progress?->status ?? 'not_started'), 'Not started'),
                'member_notes' => $progress?->member_notes,
                'reviewer_notes' => $progress?->reviewer_notes,
                'submitted_at' => $progress?->submitted_at?->format('M j, Y g:i A'),
                'completed_at' => $progress?->completed_at?->format('M j, Y g:i A'),
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requirements
     * @return array<string, int>
     */
    private function statsFor(Collection $requirements): array
    {
        $total = $requirements->count();
        $completed = $requirements->where('status', 'completed')->count();
        $inReview = $requirements->filter(
            fn (array $row) => in_array($row['status'], config('rank-advancement.review_queue', []), true)
        )->count();
        $inProgress = $requirements->whereIn('status', ['in_progress', 'rejected'])->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_review' => $inReview,
            'in_progress' => $inProgress,
            'remaining' => max(0, $total - $completed),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requirements
     */
    private function percentComplete(Collection $requirements): int
    {
        if ($requirements->isEmpty()) {
            return 100;
        }

        $completed = $requirements->where('status', 'completed')->count();

        return (int) round(($completed / $requirements->count()) * 100);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requirements
     * @return array<int, array<string, mixed>>
     */
    private function groupRequirements(Collection $requirements): array
    {
        $order = config('rank-advancement.category_order', []);

        return $requirements
            ->groupBy('category')
            ->sortBy(fn (Collection $group, string $category) => array_search($category, $order, true) !== false
                ? array_search($category, $order, true)
                : 999)
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'label' => config('rank-advancement.categories.'.$category, ucfirst(str_replace('_', ' ', $category))),
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rankLadderFor(?Rank $currentRank): array
    {
        $ranks = Rank::query()->where('is_active', true)->orderBy('sort_order')->get();
        $currentOrder = $currentRank?->sort_order ?? 0;

        return $ranks->map(function (Rank $rank) use ($currentOrder): array {
            $state = match (true) {
                $currentOrder === 0 => 'future',
                $rank->sort_order < $currentOrder => 'achieved',
                $rank->sort_order === $currentOrder => 'current',
                $rank->sort_order === $currentOrder + 1 => 'next',
                default => 'future',
            };

            return [
                'code' => $rank->code,
                'name' => $rank->name,
                'state' => $state,
            ];
        })->values()->all();
    }

    private function progressForMember(User $member, int $progressId): UserRankProgress
    {
        return UserRankProgress::query()
            ->where('user_id', $member->id)
            ->whereKey($progressId)
            ->with('requirement')
            ->firstOrFail();
    }
}
