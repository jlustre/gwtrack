<?php

namespace App\Services;

use App\Models\FnaRecord;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Fna\FnaAnalyticsService;
use App\Services\Prospects\ProspectActivityLogSummaryService;
use App\Support\MemberDisplayName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatDetailsService
{
    public const TEAM_TYPES = ['profile', 'onboarding', 'credentials', 'apprenticeship', 'training'];

    public const PERSONAL_PROGRESS_TYPES = ['profile', 'onboarding', 'credentials', 'apprenticeship', 'training'];

    public const PERSONAL_LIST_TYPES = [
        'prospects',
        'hot_prospects',
        'followups_due',
        'activities',
        'prospect_conversion',
        'recruits',
        'production',
        'fna',
    ];

    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
        private readonly DashboardStatsService $stats,
        private readonly ProfileCompletionService $profileCompletion,
        private readonly MemberProfileTabsService $memberProfileTabs,
        private readonly FnaAnalyticsService $fnaAnalytics,
        private readonly ChecklistService $checklists,
        private readonly ProspectActivityLogSummaryService $activityLogSummary,
    ) {}

    public function isValidType(string $type): bool
    {
        return in_array($type, self::TEAM_TYPES, true)
            || in_array($type, self::PERSONAL_LIST_TYPES, true);
    }

    public function isValidContext(string $type, string $context): bool
    {
        return match ($context) {
            'team' => in_array($type, self::TEAM_TYPES, true),
            'personal' => in_array($type, self::PERSONAL_PROGRESS_TYPES, true)
                || in_array($type, self::PERSONAL_LIST_TYPES, true),
            default => false,
        };
    }

    /**
     * @return array{
     *     type: string,
     *     context: string,
     *     title: string,
     *     scope: string,
     *     display: 'progress'|'list',
     *     summary: string|null,
     *     members: list<array{id: int, name: string, email: string|null, rank: string|null, percent: int, status: string}>,
     *     items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>
     * }
     */
    public function detailsFor(User $viewer, string $type, string $context = 'team'): array
    {
        abort_unless($this->isValidType($type), 404);
        abort_unless($this->isValidContext($type, $context), 404);

        if ($context === 'team') {
            return $this->teamProgressDetails($viewer, $type);
        }

        if (in_array($type, self::PERSONAL_PROGRESS_TYPES, true)) {
            return $this->personalProgressDetails($viewer, $type);
        }

        return $this->personalListDetails($viewer, $type);
    }

    /**
     * @deprecated Use detailsFor() instead.
     */
    public function membersFor(User $viewer, string $type): array
    {
        return $this->detailsFor($viewer, $type, 'team');
    }

    public function scopeLabel(User $viewer, string $context = 'team'): string
    {
        if ($context === 'personal') {
            return 'personal';
        }

        if ($viewer->hasPermissionTo('view full downline')) {
            return 'full_downline';
        }

        if ($viewer->hasPermissionTo('view direct downline')) {
            return 'direct_downline';
        }

        return 'self';
    }

    /**
     * @return array{
     *     type: string,
     *     context: string,
     *     title: string,
     *     scope: string,
     *     display: 'progress',
     *     summary: string,
     *     members: list<array{id: int, name: string, email: string|null, rank: string|null, percent: int, status: string}>,
     *     items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>
     * }
     */
    private function teamProgressDetails(User $viewer, string $type): array
    {
        $members = $this->scopedMembers($viewer)
            ->loadMissing(['profile', 'rank'])
            ->map(fn (User $member): array => $this->memberProgressRow($member, $type))
            ->sortBy([
                ['percent', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();

        $average = $members === []
            ? 0
            : (int) round(collect($members)->avg('percent'));

        return [
            'type' => $type,
            'context' => 'team',
            'title' => $this->teamTitleFor($type),
            'scope' => $this->scopeLabel($viewer, 'team'),
            'display' => 'progress',
            'summary' => count($members).' member'.(count($members) === 1 ? '' : 's').' · '.$average.'% team average',
            'members' => $members,
            'items' => [],
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     context: string,
     *     title: string,
     *     scope: string,
     *     display: 'progress'|'list',
     *     summary: string,
     *     members: list<array{id: int, name: string, email: string|null, rank: string|null, percent: int, status: string}>,
     *     items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>
     * }
     */
    private function personalProgressDetails(User $viewer, string $type): array
    {
        $viewer->loadMissing(['profile', 'rank']);
        $memberRow = $this->memberProgressRow($viewer, $type);

        return [
            'type' => $type,
            'context' => 'personal',
            'title' => $this->personalTitleFor($type),
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'progress',
            'summary' => $memberRow['percent'].'% complete',
            'members' => [$memberRow],
            'items' => $this->personalBreakdownItems($viewer, $type),
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>
     */
    private function personalBreakdownItems(User $viewer, string $type): array
    {
        if ($type === 'profile') {
            return collect($this->profileCompletion->fields($viewer))
                ->map(fn (array $field): array => [
                    'title' => $field['label'],
                    'subtitle' => $field['filled'] ? 'Complete' : 'Missing',
                    'meta' => null,
                    'url' => route('profile.edit', ['tab' => 'profile']),
                ])
                ->all();
        }

        $typeCode = match ($type) {
            'onboarding' => 'onboarding',
            'credentials' => 'licensing',
            'apprenticeship' => 'fap',
            default => null,
        };

        if ($type === 'training') {
            $lessonIds = DB::table('training_lessons')
                ->join('training_modules', 'training_modules.id', '=', 'training_lessons.training_module_id')
                ->join('training_categories', 'training_categories.id', '=', 'training_modules.training_category_id')
                ->where('training_modules.is_published', true)
                ->whereNull('training_modules.deleted_at')
                ->whereNull('training_lessons.deleted_at')
                ->whereNull('training_categories.deleted_at')
                ->orderBy('training_modules.sort_order')
                ->orderBy('training_lessons.sort_order')
                ->pluck('training_lessons.id');

            if ($lessonIds->isEmpty()) {
                return [[
                    'title' => 'No published training lessons',
                    'subtitle' => 'Training content will appear here when published.',
                    'meta' => null,
                    'url' => route('training.index'),
                ]];
            }

            $progress = DB::table('training_progress')
                ->where('user_id', $viewer->id)
                ->whereIn('training_lesson_id', $lessonIds)
                ->get(['training_lesson_id', 'status'])
                ->keyBy('training_lesson_id');

            $lessons = DB::table('training_lessons')
                ->whereIn('id', $lessonIds)
                ->orderBy('sort_order')
                ->get(['id', 'title']);

            return $lessons
                ->map(function (object $lesson) use ($progress): array {
                    $status = $progress->get($lesson->id)?->status ?? 'not_started';

                    return [
                        'title' => $lesson->title,
                        'subtitle' => $this->statusLabel($status),
                        'meta' => $status === 'completed' ? '100%' : '0%',
                        'url' => route('training.index'),
                    ];
                })
                ->all();
        }

        if ($typeCode === null || ! $this->checklists->hasTypeStarted($viewer, $typeCode)) {
            return [[
                'title' => 'Checklist not started',
                'subtitle' => 'Open the tracker to begin this milestone path.',
                'meta' => null,
                'url' => $this->trackerRouteFor($type),
            ]];
        }

        $viewer->loadMissing('profile');

        $steps = \App\Models\Checklist::query()
            ->forTypeCode($typeCode)
            ->when($typeCode === 'onboarding', fn ($query) => $query->applicableToCountry($viewer->profile?->country))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        $progress = \App\Models\ChecklistProgress::query()
            ->where('user_id', $viewer->id)
            ->memberProgress()
            ->whereIn('checklist_id', $steps->pluck('id'))
            ->get(['checklist_id', 'status'])
            ->keyBy('checklist_id');

        return $steps
            ->map(function ($step) use ($progress, $type): array {
                $status = $this->statusLabel($progress->get($step->id)?->status);
                $percent = ($progress->get($step->id)?->status ?? 'not_started') === 'completed' ? 100 : 0;

                return [
                    'title' => $step->title,
                    'subtitle' => $status,
                    'meta' => $percent.'%',
                    'url' => $this->trackerRouteFor($type),
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     type: string,
     *     context: string,
     *     title: string,
     *     scope: string,
     *     display: 'list',
     *     summary: string,
     *     members: list<array{id: int, name: string, email: string|null, rank: string|null, percent: int, status: string}>,
     *     items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>
     * }
     */
    private function personalListDetails(User $viewer, string $type): array
    {
        return match ($type) {
            'prospects' => $this->prospectListDetails($viewer, 'prospects'),
            'hot_prospects' => $this->prospectListDetails($viewer, 'hot_prospects'),
            'followups_due' => $this->followupsDueDetails($viewer),
            'activities' => $this->activitiesDetails($viewer),
            'prospect_conversion' => $this->prospectConversionDetails($viewer),
            'recruits' => $this->recruitsDetails($viewer),
            'production' => $this->productionDetails($viewer),
            'fna' => $this->fnaDetails($viewer),
            default => [
                'type' => $type,
                'context' => 'personal',
                'title' => 'Details',
                'scope' => $this->scopeLabel($viewer, 'personal'),
                'display' => 'list',
                'summary' => 'No details available.',
                'members' => [],
                'items' => [],
            ],
        };
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function prospectListDetails(User $viewer, string $type): array
    {
        $query = Prospect::query()
            ->with(['stage'])
            ->where('owner_id', $viewer->id)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at');

        if ($type === 'hot_prospects') {
            $query->where('interest_level', 'hot');
        }

        $items = $query
            ->limit(25)
            ->get()
            ->map(fn (Prospect $prospect): array => [
                'title' => $prospect->displayName(),
                'subtitle' => ucfirst((string) $prospect->interest_level).' interest',
                'meta' => $prospect->stage?->name ?? 'No stage',
                'url' => route('team.prospects.records.show', $prospect),
            ])
            ->all();

        $count = $type === 'hot_prospects'
            ? $this->stats->hotProspectCount($viewer)
            : $this->stats->prospectCount($viewer);

        return [
            'type' => $type,
            'context' => 'personal',
            'title' => $type === 'hot_prospects' ? 'Hot Prospects' : 'My Prospects',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $count.' active prospect'.($count === 1 ? '' : 's'),
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function followupsDueDetails(User $viewer): array
    {
        $rows = DB::table('prospect_followups')
            ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
            ->where('prospect_followups.assigned_user_id', $viewer->id)
            ->whereIn('prospect_followups.status', ['pending', 'overdue'])
            ->whereDate('prospect_followups.due_at', '<=', now()->toDateString())
            ->whereNull('prospect_followups.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderBy('prospect_followups.due_at')
            ->limit(25)
            ->get([
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'prospects.preferred_name',
                'prospect_followups.title',
                'prospect_followups.due_at',
                'prospect_followups.status',
            ]);

        $items = $rows->map(function (object $row): array {
            $name = filled($row->preferred_name)
                ? $row->preferred_name
                : trim($row->first_name.' '.$row->last_name);

            return [
                'title' => filled($name) ? $name : 'Unnamed prospect',
                'subtitle' => $row->title ?: 'Follow-up',
                'meta' => ucfirst((string) $row->status).' · due '.(\Carbon\Carbon::parse($row->due_at)->format('M j, Y')),
                'url' => route('team.prospects.records.show', $row->prospect_id),
            ];
        })->all();

        $count = $this->stats->followupsDueCount($viewer);

        return [
            'type' => 'followups_due',
            'context' => 'personal',
            'title' => 'Follow-Ups Due',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $count.' follow-up'.($count === 1 ? '' : 's').' due today or overdue',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function activitiesDetails(User $viewer): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();
        $summary = $this->activityLogSummary->summarize($viewer, $start, $end, 'daily');
        $metricDefinitions = config('prospects.activity_log_summary_metrics', []);

        $items = collect($summary['totals'])
            ->map(function (int $count, string $key) use ($metricDefinitions): array {
                $definition = $metricDefinitions[$key] ?? [];

                return [
                    'title' => $definition['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'subtitle' => $definition['description'] ?? null,
                    'meta' => (string) $count,
                    'url' => route('team.prospects'),
                    'sort' => $count,
                ];
            })
            ->sortByDesc('sort')
            ->values()
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'meta' => $item['meta'],
                'url' => $item['url'],
            ])
            ->all();

        $total = $this->stats->activityCount($viewer);

        return [
            'type' => 'activities',
            'context' => 'personal',
            'title' => 'My Activities',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $total.' activit'.($total === 1 ? 'y' : 'ies').' in the last 30 days',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function prospectConversionDetails(User $viewer): array
    {
        $converted = Prospect::query()
            ->where('owner_id', $viewer->id)
            ->whereNotNull('converted_to')
            ->whereNull('deleted_at')
            ->orderByDesc('conversion_at')
            ->limit(25)
            ->get();

        $active = $this->stats->prospectCount($viewer);
        $convertedCount = Prospect::query()
            ->where('owner_id', $viewer->id)
            ->whereNotNull('converted_to')
            ->whereNull('deleted_at')
            ->count();
        $rate = $this->stats->prospectConversionRate($viewer);

        $items = $converted
            ->map(fn (Prospect $prospect): array => [
                'title' => $prospect->displayName(),
                'subtitle' => 'Converted to '.ucfirst((string) $prospect->converted_to),
                'meta' => $prospect->conversion_at?->format('M j, Y') ?? 'Recently converted',
                'url' => route('team.prospects.records.show', $prospect),
            ])
            ->all();

        if ($items === []) {
            $items[] = [
                'title' => 'No conversions recorded yet',
                'subtitle' => 'Converted prospects will appear here.',
                'meta' => null,
                'url' => route('team.prospects.analytics'),
            ];
        }

        return [
            'type' => 'prospect_conversion',
            'context' => 'personal',
            'title' => 'Prospect Conversion',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $rate.'% conversion rate · '.$convertedCount.' converted · '.$active.' active',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function recruitsDetails(User $viewer): array
    {
        $recruits = $this->hierarchy
            ->descendantsQuery($viewer)
            ->with('rank')
            ->orderByDesc('joined_at')
            ->limit(25)
            ->get();

        $count = $this->stats->recruitCount($viewer);

        $items = $recruits
            ->map(fn (User $recruit): array => [
                'title' => MemberDisplayName::for($recruit),
                'subtitle' => $recruit->rank?->code ?? 'Member',
                'meta' => $recruit->joined_at?->format('M j, Y') ?? 'Join date pending',
                'url' => route('team.member.profile', $recruit),
            ])
            ->all();

        if ($items === []) {
            $items[] = [
                'title' => 'No recruits yet',
                'subtitle' => 'Members you sponsor will appear here.',
                'meta' => null,
                'url' => route('profile.edit', ['tab' => 'recruits']),
            ];
        }

        return [
            'type' => 'recruits',
            'context' => 'personal',
            'title' => 'My Recruits',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $count.' recruit'.($count === 1 ? '' : 's').' in your downline',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function productionDetails(User $viewer): array
    {
        $tabs = $this->memberProfileTabs->forUser($viewer);
        $rows = $tabs['annualPremium'] ?? [];
        $total = $this->stats->annualProductionTotal($viewer);

        $items = collect($rows)
            ->map(fn (array $row): array => [
                'title' => $row['description'] ?? 'Production item',
                'subtitle' => $row['source'] ?? 'Production',
                'meta' => ($row['annual_premium'] ?? '$0').' · '.($row['posted_at'] ?? '—'),
                'url' => route('profile.edit', ['tab' => 'annual-premium']),
            ])
            ->all();

        return [
            'type' => 'production',
            'context' => 'personal',
            'title' => 'My Production',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => '$'.number_format($total).' annual production total',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return array{type: string, context: string, title: string, scope: string, display: 'list', summary: string, members: array, items: list<array{title: string, subtitle: string|null, meta: string|null, url: string|null}>}
     */
    private function fnaDetails(User $viewer): array
    {
        $summary = $this->fnaAnalytics->summaryFor($viewer);

        $records = FnaRecord::query()
            ->where('owner_user_id', $viewer->id)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get();

        $items = $records
            ->map(fn (FnaRecord $record): array => [
                'title' => $record->client_name ?: 'Unnamed client',
                'subtitle' => ucfirst(str_replace('_', ' ', (string) $record->status)),
                'meta' => $record->updated_at?->format('M j, Y') ?? 'Recently updated',
                'url' => route('team.fna.dashboard'),
            ])
            ->all();

        if ($items === []) {
            $items[] = [
                'title' => 'No FNA records yet',
                'subtitle' => 'Submitted FNAs will appear here.',
                'meta' => null,
                'url' => route('team.fna.dashboard'),
            ];
        }

        return [
            'type' => 'fna',
            'context' => 'personal',
            'title' => 'My FNA Progress',
            'scope' => $this->scopeLabel($viewer, 'personal'),
            'display' => 'list',
            'summary' => $summary['approved_fnas'].'/'.$summary['total_fnas'].' approved',
            'members' => [],
            'items' => $items,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function scopedMembers(User $viewer): Collection
    {
        return $this->hierarchy
            ->dashboardMembersQuery($viewer)
            ->orderBy('users.name')
            ->get();
    }

    /**
     * @return array{id: int, name: string, email: string|null, rank: string|null, percent: int, status: string}
     */
    private function memberProgressRow(User $member, string $type): array
    {
        $percent = $this->percentFor($member, $type);

        return [
            'id' => $member->id,
            'name' => MemberDisplayName::for($member),
            'email' => $member->email,
            'rank' => $member->rank?->code ?? 'FA',
            'percent' => $percent,
            'status' => $this->statusLabelForPercent($percent),
        ];
    }

    private function percentFor(User $member, string $type): int
    {
        return match ($type) {
            'profile' => $this->profileCompletion->percent($member),
            'onboarding' => $this->stats->onboardingPercent($member),
            'credentials' => $this->stats->licensingPercent($member),
            'apprenticeship' => $this->stats->apprenticeshipPercent($member),
            'training' => $this->stats->trainingPercent($member),
            default => 0,
        };
    }

    private function statusLabelForPercent(int $percent): string
    {
        if ($percent >= 100) {
            return 'Complete';
        }

        if ($percent <= 0) {
            return 'Not started';
        }

        return 'In progress';
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Complete',
            'pending_confirmation' => 'Pending review',
            'rejected' => 'Needs revision',
            'ready_for_review' => 'Ready for review',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'in_progress' => 'In progress',
            'pending' => 'Pending',
            default => 'Not started',
        };
    }

    private function teamTitleFor(string $type): string
    {
        return match ($type) {
            'profile' => 'Team Profile Completion',
            'onboarding' => 'Team Onboarding',
            'credentials' => 'Team Licensing',
            'apprenticeship' => 'Team FAP',
            'training' => 'Team CFM Training',
            default => 'Team Progress',
        };
    }

    private function personalTitleFor(string $type): string
    {
        return match ($type) {
            'profile' => 'My Profile Completion',
            'onboarding' => 'My Onboarding',
            'credentials' => 'My Licensing',
            'apprenticeship' => 'My FAP',
            'training' => 'My CFM Training',
            default => 'My Progress',
        };
    }

    private function trackerRouteFor(string $type): ?string
    {
        return match ($type) {
            'profile' => route('profile.edit', ['tab' => 'profile']),
            'onboarding' => route('onboarding.index'),
            'credentials' => route('licensing.index'),
            'apprenticeship' => route('apprenticeship.index'),
            'training' => route('training.index'),
            default => null,
        };
    }
}
