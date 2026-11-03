<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeadershipReportService
{
    public function __construct(private readonly DownlineHierarchyService $hierarchy) {}

    /**
     * @return array{
     *     period: string,
     *     period_label: string,
     *     period_start: Carbon,
     *     period_end: Carbon,
     *     generated_at: Carbon,
     *     summary: array<string, int|float>,
     *     member_rows: list<array<string, int|string|null>>
     * }
     */
    public function build(User $viewer, string $period = 'monthly', ?int $limit = 25, array $filters = [], int $page = 1): array
    {
        abort_unless($viewer->can('view leadership reports'), 403);
        abort_unless(in_array($period, ['monthly', 'quarterly', 'annual'], true), 422);

        [$start, $end, $periodLabel] = $this->periodBounds($period);
        $scopedMembers = $this->hierarchy->dashboardMembersQuery($viewer)
            ->with(['rank:id,name', 'team:id,name', 'roles:id,name'])
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.rank_id', 'users.team_id', 'users.is_active', 'users.created_at']);
        $members = $scopedMembers
            ->filter(fn (User $member): bool => $this->matchesFilters($member, $filters))
            ->values();
        $memberIds = $members->modelKeys();

        $checklistStats = $this->checklistStats($memberIds);
        $trainingStats = $this->trainingStats($memberIds, $start, $end);
        $rankStats = $this->rankStats($memberIds);
        $mentoringStats = $this->mentoringStats($memberIds, $start, $end);
        $rows = $members->map(function (User $member) use ($checklistStats, $trainingStats, $rankStats, $mentoringStats): array {
            $checklists = $checklistStats->get($member->id, collect());
            $onboarding = $this->completionFor($checklists->get('onboarding'));
            $licensing = $this->completionFor($checklists->get('licensing'));
            $apprenticeship = $this->completionFor($checklists->get('fap'));

            return [
                'name' => $member->name,
                'rank' => $member->rank?->name ?? 'Unranked',
                'team' => $member->team?->name ?? 'Unassigned',
                'role' => $member->roles->pluck('name')->map(fn (string $role): string => str($role)->headline()->toString())->implode(', '),
                'status' => $member->is_active ? 'Active' : 'Inactive',
                'joined' => $member->created_at?->format('Y-m-d'),
                'onboarding' => $onboarding['percent'],
                'licensing' => $licensing['percent'],
                'apprenticeship' => $apprenticeship['percent'],
                'active_apprentices' => (int) $mentoringStats['by_mentor']->get($member->id, 0),
                'lessons_completed' => (int) $trainingStats['by_member']->get($member->id, ['lessons' => 0, 'assessments' => 0])['lessons'],
                'assessments_passed' => (int) $trainingStats['by_member']->get($member->id, ['lessons' => 0, 'assessments' => 0])['assessments'],
                'rank_progress' => $this->completionFor($rankStats->get($member->id))['percent'],
            ];
        });

        $summary = [
            'members' => $members->count(),
            'new_members' => $members->filter(fn (User $member): bool => $member->created_at?->betweenIncluded($start, $end) ?? false)->count(),
            'onboarding_average' => $this->averagePercent($rows, 'onboarding'),
            'licensing_average' => $this->averagePercent($rows, 'licensing'),
            'apprenticeship_average' => $this->averagePercent($rows, 'apprenticeship'),
            'active_apprenticeships' => (int) $mentoringStats['active'],
            'completed_apprenticeships' => (int) $mentoringStats['completed'],
            'new_mentor_assignments' => (int) $mentoringStats['new'],
            'lessons_completed' => (int) $trainingStats['lessons'],
            'assessments_passed' => (int) $trainingStats['assessments'],
            'rank_progress_average' => $this->averagePercent($rows, 'rank_progress'),
        ];
        $page = max(1, $page);
        $totalRows = $rows->count();
        $offset = $limit === null ? 0 : ($page - 1) * max(1, $limit);
        $visibleRows = $limit === null ? $rows : $rows->slice($offset, max(1, $limit))->values();

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'period_start' => $start,
            'period_end' => $end,
            'generated_at' => now(),
            'summary' => $summary,
            'member_rows' => $visibleRows->all(),
            'pagination' => [
                'page' => $page,
                'from' => $totalRows === 0 || $limit === null ? ($totalRows > 0 ? 1 : 0) : $offset + 1,
                'to' => $limit === null ? $totalRows : min($offset + max(1, $limit), $totalRows),
                'total' => $totalRows,
                'has_previous' => $limit !== null && $page > 1,
                'has_more' => $limit !== null && $offset + max(1, $limit) < $totalRows,
            ],
            'filter_options' => [
                'teams' => $scopedMembers->filter(fn (User $member): bool => $member->team !== null)->map(fn (User $member): array => [
                    'id' => $member->team_id,
                    'name' => $member->team->name,
                ])->unique('id')->sortBy('name')->values(),
                'ranks' => $scopedMembers->filter(fn (User $member): bool => $member->rank !== null)->map(fn (User $member): array => [
                    'id' => $member->rank_id,
                    'name' => $member->rank->name,
                ])->unique('id')->sortBy('name')->values(),
                'roles' => $scopedMembers->flatMap(fn (User $member) => $member->roles->pluck('name'))->unique()->sort()->values(),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function matchesFilters(User $member, array $filters): bool
    {
        if (filled($filters['team_id'] ?? null) && (string) $member->team_id !== (string) $filters['team_id']) {
            return false;
        }

        if (filled($filters['rank_id'] ?? null) && (string) $member->rank_id !== (string) $filters['rank_id']) {
            return false;
        }

        if (($filters['status'] ?? '') === 'active' && ! $member->is_active) {
            return false;
        }

        if (($filters['status'] ?? '') === 'inactive' && $member->is_active) {
            return false;
        }

        return blank($filters['role'] ?? null) || $member->roles->contains('name', $filters['role']);
    }

    /**
     * @param  list<int>  $memberIds
     * @return Collection<int, Collection<string, array{completed: int, total: int}>>
     */
    private function checklistStats(array $memberIds): Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        return DB::table('checklist_progress')
            ->join('checklists', 'checklists.id', '=', 'checklist_progress.checklist_id')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->whereIn('checklist_progress.user_id', $memberIds)
            ->whereIn('checklist_types.code', ['onboarding', 'licensing', 'fap'])
            ->whereNull('checklist_types.deleted_at')
            ->whereNull('checklists.deleted_at')
            ->selectRaw('checklist_progress.user_id, checklist_types.code, COUNT(DISTINCT checklist_progress.checklist_id) as total')
            ->selectRaw("COUNT(DISTINCT CASE WHEN checklist_progress.status IN ('completed', 'approved', 'confirmed') THEN checklist_progress.checklist_id END) as completed")
            ->groupBy('checklist_progress.user_id', 'checklist_types.code')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $items): Collection => $items->keyBy('code')->map(fn ($item): array => [
                'completed' => (int) $item->completed,
                'total' => (int) $item->total,
            ]));
    }

    /**
     * @param  list<int>  $memberIds
     * @return array{lessons: int, assessments: int, by_member: Collection<int, array{lessons: int, assessments: int}>}
     */
    private function trainingStats(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['lessons' => 0, 'assessments' => 0, 'by_member' => collect()];
        }

        $lessons = DB::table('training_progress')
            ->whereIn('user_id', $memberIds)
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $assessments = DB::table('assessment_attempts')
            ->whereIn('user_id', $memberIds)
            ->where('passed', true)
            ->whereNull('deleted_at')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $byMember = collect($memberIds)->mapWithKeys(fn (int $id): array => [$id => [
            'lessons' => (int) $lessons->get($id, 0),
            'assessments' => (int) $assessments->get($id, 0),
        ]]);

        return [
            'lessons' => (int) $lessons->sum(),
            'assessments' => (int) $assessments->sum(),
            'by_member' => $byMember,
        ];
    }

    /**
     * @param  list<int>  $memberIds
     * @return Collection<int, array{completed: int, total: int}>
     */
    private function rankStats(array $memberIds): Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        return DB::table('user_rank_progress')
            ->join('rank_requirements', 'rank_requirements.id', '=', 'user_rank_progress.rank_requirement_id')
            ->whereIn('user_rank_progress.user_id', $memberIds)
            ->where('rank_requirements.is_active', true)
            ->whereNull('rank_requirements.deleted_at')
            ->whereNull('user_rank_progress.deleted_at')
            ->selectRaw('user_rank_progress.user_id, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN user_rank_progress.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('user_rank_progress.user_id')
            ->get()
            ->keyBy('user_id')
            ->map(fn ($item): array => [
                'completed' => (int) $item->completed,
                'total' => (int) $item->total,
            ]);
    }

    /**
     * @param  list<int>  $memberIds
     * @return array{active: int, completed: int, new: int, by_mentor: Collection<int, int>}
     */
    private function mentoringStats(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['active' => 0, 'completed' => 0, 'new' => 0, 'by_mentor' => collect()];
        }

        $assignments = DB::table('mentor_assignments')
            ->whereIn('apprentice_id', $memberIds)
            ->whereNull('deleted_at');
        $mentorActivity = DB::table('mentor_assignments')
            ->whereIn('mentor_id', $memberIds)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->selectRaw('mentor_id, COUNT(*) as total')
            ->groupBy('mentor_id')
            ->pluck('total', 'mentor_id');

        return [
            'active' => (clone $assignments)->where('status', 'active')->count(),
            'completed' => (clone $assignments)->where('status', 'completed')->count(),
            'new' => (clone $assignments)->whereBetween('created_at', [$start, $end])->count(),
            'by_mentor' => $mentorActivity,
        ];
    }

    /**
     * @param  array{completed: int, total: int}|null  $stats
     * @return array{completed: int, total: int, percent: int}
     */
    private function completionFor(?array $stats): array
    {
        $completed = $stats['completed'] ?? 0;
        $total = $stats['total'] ?? 0;

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * @param  list<array<string, int|string|null>>  $rows
     */
    private function averagePercent(Collection $rows, string $column): int
    {
        return count($rows) > 0
            ? (int) round($rows->avg($column))
            : 0;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function periodBounds(string $period): array
    {
        $end = now()->endOfDay();
        $start = match ($period) {
            'quarterly' => now()->startOfQuarter(),
            'annual' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
        $label = match ($period) {
            'quarterly' => 'Quarter to date',
            'annual' => 'Year to date',
            default => 'Month to date',
        };

        return [$start, $end, $label];
    }
}
