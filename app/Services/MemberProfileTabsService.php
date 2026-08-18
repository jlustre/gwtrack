<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\MemberProductionEntry;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Support\ChecklistDueDisplay;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MemberProfileTabsService
{
    private const CHECKLIST_DATE_FORMAT = 'm/d/Y';

    public function __construct(private readonly ChecklistService $checklists) {}

    public function forUser(User $user): array
    {
        $user->loadMissing(['profile', 'rank', 'mentor']);

        $onboarding = $this->onboardingRows($user);
        $fap = $this->checklistRows($user, 'fap', 'Field Apprenticeship Program');
        $cfm = $this->checklistRows($user, 'cfm-training');
        $recruits = $this->recruitRows($user);
        $annualPremium = $this->annualPremiumRows($user, $onboarding, $fap, $cfm);

        return [
            'recruits' => $recruits,
            'recruitsTotal' => count($recruits),
            'recruitsDirectTotal' => collect($recruits)->where('level', 1)->count(),
            'annualPremium' => $annualPremium,
            'annualPremiumTotal' => $this->sumAnnualPremium($annualPremium),
            'checklistSummaries' => $this->checklistSummaries($user),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function checklistSummariesFor(User $user): array
    {
        return $this->checklistSummaries($user);
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string|null,
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     started_at: string|null,
     *     started_by: string|null,
     *     route: string|null,
     *     items: list<array<string, string>>
     * }>
     */
    private function checklistSummaries(User $user): array
    {
        return UserChecklistTypeStart::query()
            ->where('user_id', $user->id)
            ->with(['checklistType', 'starter'])
            ->get()
            ->sortBy(fn (UserChecklistTypeStart $start): int => $start->checklistType?->sort_order ?? 999)
            ->map(fn (UserChecklistTypeStart $start) => $this->buildChecklistSummary($user, $start))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildChecklistSummary(User $user, UserChecklistTypeStart $start): ?array
    {
        $type = $start->checklistType;

        if (! $type) {
            return null;
        }

        $code = $type->code;
        $steps = $this->stepsForSummary($user, $code);
        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));
        $startDate = $start->started_at?->copy()->startOfDay();
        $completed = $steps->filter(
            fn (Checklist $step): bool => ($progress->get($step->id)?->status ?? 'not_started') === 'completed',
        )->count();
        $typeCompletionDue = $startDate && $type->max_complete_days
            ? $this->checklists->typeCompletionDueDate($startDate, $code)
            : null;

        return [
            'code' => $code,
            'name' => $type->name,
            'description' => $type->description,
            'percent' => $this->percentForType($user, $code, $steps),
            'completed' => $completed,
            'total' => $steps->count(),
            'started_at' => $start->started_at?->format(self::CHECKLIST_DATE_FORMAT),
            'started_by' => $start->starter?->name,
            'due_at' => $typeCompletionDue?->format(self::CHECKLIST_DATE_FORMAT),
            'is_due_overdue' => $typeCompletionDue
                && $completed < $steps->count()
                && ChecklistDueDisplay::isOverdue($typeCompletionDue),
            'route' => $this->trackerRouteFor($code),
            'items' => $steps
                ->map(function (Checklist $step) use ($user, $code, $progress, $startDate): array {
                    $dueAt = $startDate
                        ? $this->checklists->expectedDueDate($step->nth_day, $startDate)
                        : null;

                    return $this->formatChecklistRow(
                        $step->title,
                        $this->categoryLabelForStep($user, $code, $step),
                        (bool) $step->is_required,
                        $progress->get($step->id),
                        $dueAt,
                    );
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, Checklist>
     */
    private function stepsForSummary(User $user, string $typeCode): Collection
    {
        if ($typeCode === 'onboarding') {
            return $this->checklists->activeSteps('onboarding', $user->profile?->country);
        }

        if ($typeCode === 'fap') {
            return $this->checklists->activeSteps('fap', null, 'Field Apprenticeship Program');
        }

        return $this->checklists->activeSteps($typeCode);
    }

    /**
     * @param  Collection<int, Checklist>  $steps
     */
    private function percentForType(User $user, string $typeCode, Collection $steps): int
    {
        if ($steps->isEmpty()) {
            return 0;
        }

        return $this->checklists->checklistPercent($steps->pluck('id')->all(), $user->id);
    }

    private function categoryLabelForStep(User $user, string $typeCode, Checklist $step): string
    {
        return match ($typeCode) {
            'onboarding' => $step->country ?: 'Global',
            default => $step->group_label ?? '—',
        };
    }

    private function trackerRouteFor(string $typeCode): ?string
    {
        return match ($typeCode) {
            'onboarding' => 'onboarding.index',
            'licensing' => 'licensing.index',
            'fap' => 'apprenticeship.index',
            'cfm-training' => 'cfm-training.index',
            default => null,
        };
    }

    public function annualPremiumTotal(User $user): int
    {
        $user->loadMissing(['profile', 'rank', 'mentor']);

        return $this->sumAnnualPremium($this->annualPremiumRows(
            $user,
            $this->onboardingRows($user),
            $this->checklistRows($user, 'fap', 'Field Apprenticeship Program'),
            $this->checklistRows($user, 'cfm-training'),
        ));
    }

    /**
     * @param  list<array{premium_amount: int}>  $rows
     */
    private function sumAnnualPremium(array $rows): int
    {
        return (int) collect($rows)->sum('premium_amount');
    }

    private function onboardingRows(User $user): array
    {
        if (! $this->checklists->hasTypeStarted($user, 'onboarding')) {
            return [];
        }

        $steps = $this->checklists->activeSteps('onboarding', $user->profile?->country);
        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));

        return $steps
            ->map(fn (Checklist $step) => $this->formatChecklistRow(
                $step->title,
                $step->country ?: 'Global',
                (bool) $step->is_required,
                $progress->get($step->id)
            ))
            ->values()
            ->all();
    }

    private function checklistRows(User $user, string $typeCode, ?string $groupLabel = null): array
    {
        if (! $this->checklists->hasTypeStarted($user, $typeCode)) {
            return [];
        }

        $steps = $this->checklists->activeSteps($typeCode, null, $groupLabel);
        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));

        return $steps
            ->map(function (Checklist $step) use ($progress): array {
                return $this->formatChecklistRow(
                    $step->title,
                    $step->group_label ?? '—',
                    (bool) $step->is_required,
                    $progress->get($step->id)
                );
            })
            ->values()
            ->all();
    }

    private function formatChecklistRow(
        string $title,
        string $category,
        bool $required,
        mixed $progress,
        ?CarbonInterface $dueAt = null,
    ): array {
        $status = $progress?->status ?? 'not_started';

        return [
            'item' => $title,
            'category' => $category,
            'required' => $required ? 'Yes' : 'No',
            'status' => str($status)->replace('_', ' ')->title()->toString(),
            'status_key' => $status,
            'due_at' => $dueAt ? $dueAt->format(self::CHECKLIST_DATE_FORMAT) : '—',
            'is_due_overdue' => ChecklistDueDisplay::isOverdue($dueAt, $status),
            'submitted_at' => $this->formatChecklistDate($progress?->submitted_at ?? null),
            'completed_at' => $this->formatChecklistDate($progress?->completed_at ?? null),
        ];
    }

    private function recruitRows(User $user): array
    {
        $hierarchy = app(DownlineHierarchyService::class);

        return $hierarchy->descendantsQuery($user)
            ->addSelect('user_hierarchy_paths.depth as recruit_level')
            ->with(['rank', 'mentor', 'profile', 'sponsor', 'roles'])
            ->orderBy('user_hierarchy_paths.depth')
            ->orderByDesc('users.joined_at')
            ->orderBy('users.name')
            ->get()
            ->map(function (User $recruit) use ($hierarchy): array {
                $progress = $hierarchy->progressSummary($recruit);
                $level = (int) ($recruit->recruit_level ?? 1);

                $country = $recruit->profile?->country;
                $province = $recruit->profile?->province;

                return [
                    'level' => $level,
                    'level_label' => (string) $level,
                    'name' => $recruit->name,
                    'email' => $recruit->email,
                    'profile_photo_url' => $recruit->profilePhotoUrl(),
                    'phone' => $recruit->profile?->phone ?: '—',
                    'role' => $this->recruitRoleLabel($recruit),
                    'rank' => $recruit->rank?->code ?? '—',
                    'status' => $recruit->is_active ? 'Active' : 'Inactive',
                    'status_key' => $recruit->is_active ? 'active' : 'inactive',
                    'sponsor' => $recruit->sponsor?->name ?? '—',
                    'cfm' => $recruit->mentor?->name ?? 'Unassigned',
                    'province' => $province ?: '—',
                    'country' => $country ?: '—',
                    'country_flag' => $this->countryFlag($country),
                    'joined_at' => $recruit->joined_at?->format('M j, Y') ?? '—',
                    'onboarding' => \App\Support\ChecklistProgressDisplay::label($progress['onboarding'] ?? 0),
                    'fap' => \App\Support\ChecklistProgressDisplay::label($progress['apprenticeship'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function annualPremiumRows(User $user, array $onboarding, array $fap, array $cfm): array
    {
        $rows = collect();
        $year = now()->year;

        foreach ($onboarding as $row) {
            if ($row['status_key'] !== 'completed') {
                continue;
            }

            $amount = $row['required'] === 'Yes' ? 1200 : 600;
            $rows->push($this->formatAnnualPremiumRow($year, 'Onboarding', $row['item'], $amount, $row['completed_at']));
        }

        foreach ($fap as $row) {
            if ($row['status_key'] !== 'completed') {
                continue;
            }

            $amount = $row['required'] === 'Yes' ? 3500 : 1800;
            $rows->push($this->formatAnnualPremiumRow($year, 'Field Apprenticeship', $row['item'], $amount, $row['completed_at']));
        }

        foreach ($cfm as $row) {
            if ($row['status_key'] !== 'completed') {
                continue;
            }

            $amount = $row['required'] === 'Yes' ? 2400 : 1200;
            $rows->push($this->formatAnnualPremiumRow($year, 'CFM Training', $row['item'], $amount, $row['completed_at']));
        }

        User::query()
            ->where('sponsor_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('joined_at')
            ->get(['id', 'name', 'joined_at'])
            ->each(function (User $recruit) use ($rows, $year): void {
                $rows->push($this->formatAnnualPremiumRow(
                    $year,
                    'Direct Recruit',
                    $recruit->name.' — new team member',
                    5000,
                    $recruit->joined_at?->format('M j, Y') ?? '—'
                ));
            });

        MemberProductionEntry::query()
            ->where('user_id', $user->id)
            ->where('status', 'posted')
            ->whereYear('posted_at', $year)
            ->orderByDesc('posted_at')
            ->get()
            ->each(function (MemberProductionEntry $entry) use ($rows, $year): void {
                $rows->push($this->formatAnnualPremiumRow(
                    $year,
                    'Manual Entry',
                    $entry->description ?: ($entry->policy_reference ?: 'Production entry'),
                    (int) round((float) $entry->annual_premium),
                    $entry->posted_at?->format('M j, Y') ?? '—',
                ));
            });

        if ($rows->isEmpty()) {
            return [[
                'period' => (string) $year,
                'source' => '—',
                'source_key' => '',
                'description' => 'Annual premium production will appear as you complete milestones and build your team.',
                'annual_premium' => '$0',
                'premium_amount' => 0,
                'status' => '—',
                'status_key' => '',
                'posted_at' => '—',
            ]];
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['premium_amount'])
            ->values()
            ->all();
    }

    private function formatAnnualPremiumRow(int $year, string $source, string $description, int $amount, string $postedAt): array
    {
        return [
            'period' => (string) $year,
            'source' => $source,
            'source_key' => $source,
            'description' => $description,
            'annual_premium' => '$'.number_format($amount),
            'premium_amount' => $amount,
            'status' => 'Posted',
            'status_key' => 'posted',
            'posted_at' => $postedAt !== '—' ? $postedAt : '—',
        ];
    }

    private function formatChecklistDate(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->format(self::CHECKLIST_DATE_FORMAT);
    }

    private function formatDate(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->format('M j, Y');
    }

    private function recruitRoleLabel(User $user): string
    {
        if ($user->hasRole('agency-owner')) {
            return 'AO';
        }

        if ($user->hasRole('certified-field-mentor')) {
            return 'CFM';
        }

        return 'Member';
    }

    private function countryFlag(?string $country): string
    {
        return match (strtolower((string) $country)) {
            'canada', 'ca' => 'CA',
            'united states', 'usa', 'us', 'u.s.', 'u.s.a.' => 'US',
            'philippines', 'ph' => 'PH',
            'global', '' => 'GL',
            default => strtoupper(str($country)->substr(0, 2)->value() ?: 'GL'),
        };
    }
}
