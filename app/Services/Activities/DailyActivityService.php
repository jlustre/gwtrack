<?php

namespace App\Services\Activities;

use App\Models\GoalActivityLog;
use App\Models\GoalActivityTarget;
use App\Models\User;
use App\Services\Goals\GoalBridgeService;
use App\Services\Goals\GoalMetricResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class DailyActivityService
{
    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    /**
     * @var list<string>
     */
    public const MANUAL_KEYS = [
        'contacts',
        'followups_completed',
        'presentation_invitations',
        'presentations',
        'fna_started',
    ];

    /**
     * @var list<string>
     */
    public const SYSTEM_KEYS = [
        'new_prospects',
        'monthly_premium',
        'recruits',
    ];

    /**
     * Planner / target activity_key aliases keyed by scorecard metric key.
     *
     * @var array<string, list<string>>
     */
    private const TARGET_KEY_ALIASES = [
        'new_prospects' => ['new_prospects'],
        'monthly_premium' => ['monthly_premium', 'monthly_production', 'weekly_production', 'annual_production'],
        'recruits' => ['recruits', 'annual_recruits', 'monthly_recruits'],
        'contacts' => ['contacts', 'prospect_contacts', 'daily_contacts', 'recruiting_contacts'],
        'followups_completed' => ['followups_completed'],
        'presentation_invitations' => ['presentation_invitations', 'invitations', 'recruiting_invitations'],
        'presentations' => ['presentations', 'recruiting_presentations'],
        'fna_started' => ['fna_started', 'fnas', 'fna_completed'],
    ];

    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
        private readonly GoalBridgeService $goalBridge,
    ) {}

    /**
     * @return list<string>
     */
    public function allowedPeriods(): array
    {
        return [self::PERIOD_DAILY, self::PERIOD_WEEKLY, self::PERIOD_MONTHLY];
    }

    public function canLogFnaStarted(User $user): bool
    {
        $user->loadMissing('profile');

        return filled($user->profile?->license_number);
    }

    /**
     * @return list<string>
     */
    public function manualKeysFor(User $user): array
    {
        return array_values(array_filter(
            self::MANUAL_KEYS,
            fn (string $key): bool => $key !== 'fna_started' || $this->canLogFnaStarted($user),
        ));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function periodBounds(string $periodType): array
    {
        return match ($periodType) {
            self::PERIOD_DAILY => [now()->startOfDay(), now()->endOfDay(), now()->format('M j, Y')],
            self::PERIOD_MONTHLY => [now()->startOfMonth(), now()->endOfMonth(), now()->format('F Y')],
            default => [now()->startOfWeek(), now()->endOfWeek(), 'Week of '.now()->startOfWeek()->format('M j')],
        };
    }

    /**
     * @return array{
     *     period_type: string,
     *     period_label: string,
     *     can_log_fna: bool,
     *     system: list<array<string, mixed>>,
     *     manual: list<array<string, mixed>>,
     *     chart: list<array<string, mixed>>,
     *     overall_score: int
     * }
     */
    public function dashboardFor(User $user, string $periodType = self::PERIOD_DAILY): array
    {
        if (! in_array($periodType, $this->allowedPeriods(), true)) {
            $periodType = self::PERIOD_DAILY;
        }

        [$start, $end, $label] = $this->periodBounds($periodType);
        $definitions = $this->metricDefinitions($user);
        $system = [];
        $manual = [];
        $chart = [];
        $percents = [];

        foreach ($definitions as $definition) {
            $actual = $this->metricResolver->resolve(
                $user,
                $definition['key'],
                $start->copy(),
                $end->copy(),
            );
            $target = $this->targetFor($user, $definition['key'], $periodType);
            $percent = $target > 0 ? (int) min(100, round(($actual / $target) * 100)) : 0;
            $percents[] = $percent;

            $row = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'actual' => $definition['format'] === 'currency'
                    ? round($actual, 0)
                    : round($actual, 1),
                'target' => $definition['format'] === 'currency'
                    ? round($target, 0)
                    : round($target, 1),
                'percent' => $percent,
                'format' => $definition['format'],
                'subtitle' => $definition['subtitle'],
                'description' => $definition['description'],
                'examples' => $definition['examples'],
                'href' => $definition['href'],
                'today_manual' => $this->manualTallyForDate($user, $definition['key'], now()->toDateString()),
                'adjustable' => $definition['adjustable'],
            ];

            if ($definition['adjustable']) {
                $manual[] = $row;
            } else {
                $system[] = $row;
            }

            $chart[] = $row;
        }

        return [
            'period_type' => $periodType,
            'period_label' => $label,
            'can_log_fna' => $this->canLogFnaStarted($user),
            'system' => $system,
            'manual' => $manual,
            'chart' => $chart,
            'overall_score' => $percents === [] ? 0 : (int) round(array_sum($percents) / count($percents)),
        ];
    }

    public function increment(User $user, string $activityKey): void
    {
        $this->adjust($user, $activityKey, 1);
    }

    public function decrement(User $user, string $activityKey): void
    {
        $this->adjust($user, $activityKey, -1);
    }

    public function manualTallyForDate(User $user, string $activityKey, string $date): float
    {
        return (float) GoalActivityLog::query()
            ->where('user_id', $user->id)
            ->where('activity_key', $activityKey)
            ->whereDate('logged_for_date', $date)
            ->where('source', 'manual')
            ->sum('value');
    }

    public function manualTallyForPeriod(User $user, string $activityKey, Carbon $start, Carbon $end): float
    {
        return (float) GoalActivityLog::query()
            ->where('user_id', $user->id)
            ->where('activity_key', $activityKey)
            ->whereDate('logged_for_date', '>=', $start->toDateString())
            ->whereDate('logged_for_date', '<=', $end->toDateString())
            ->where('source', 'manual')
            ->sum('value');
    }

    public function targetFor(User $user, string $metricKey, string $periodType): float
    {
        $aliases = self::TARGET_KEY_ALIASES[$metricKey] ?? [$metricKey];
        $lookupPeriod = $periodType === 'annual' ? 'monthly' : $periodType;

        $target = (float) GoalActivityTarget::query()
            ->whereHas('goal', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
            ->whereIn('activity_key', $aliases)
            ->where('period_type', $lookupPeriod)
            ->sum('target_value');

        if ($target > 0) {
            return $target;
        }

        return $this->defaultTarget($metricKey, $periodType);
    }

    private function adjust(User $user, string $activityKey, int $delta): void
    {
        if (! in_array($activityKey, $this->manualKeysFor($user), true)) {
            throw new InvalidArgumentException('This activity cannot be adjusted manually.');
        }

        $today = now()->toDateString();
        $todayManual = $this->manualTallyForDate($user, $activityKey, $today);

        if ($delta < 0 && $todayManual + $delta < 0) {
            return;
        }

        DB::transaction(function () use ($user, $activityKey, $delta, $today): void {
            GoalActivityLog::query()->create([
                'user_id' => $user->id,
                'goal_id' => null,
                'activity_key' => $activityKey,
                'value' => $delta,
                'logged_for_date' => $today,
                'source' => 'manual',
                'notes' => $delta > 0 ? 'Daily activity increment' : 'Daily activity decrement',
            ]);
        });

        $this->goalBridge->syncMetrics($user, [$activityKey]);
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     subtitle: string,
     *     description: string|null,
     *     examples: list<string>,
     *     format: string,
     *     adjustable: bool,
     *     href: string|null
     * }>
     */
    private function metricDefinitions(User $user): array
    {
        $help = config('goals-planning.activity_scorecard', []);

        $definitions = [
            [
                'key' => 'new_prospects',
                'label' => 'Leads / Prospects',
                'subtitle' => 'Added in Prospects',
                'format' => 'number',
                'adjustable' => false,
                'href' => Route::has('team.prospects') ? route('team.prospects') : null,
            ],
            [
                'key' => 'monthly_premium',
                'label' => 'Production',
                'subtitle' => 'Premium from production entries',
                'format' => 'currency',
                'adjustable' => false,
                'href' => Route::has('team.production') ? route('team.production') : null,
            ],
            [
                'key' => 'recruits',
                'label' => 'Recruits',
                'subtitle' => 'Associate conversions',
                'format' => 'number',
                'adjustable' => false,
                'href' => Route::has('team.recruiting.index') ? route('team.recruiting.index') : null,
            ],
            [
                'key' => 'contacts',
                'label' => 'Initial phone calls',
                'subtitle' => 'CRM contacts + today’s tally',
                'format' => 'number',
                'adjustable' => true,
                'href' => null,
            ],
            [
                'key' => 'followups_completed',
                'label' => 'Follow-ups',
                'subtitle' => 'Completed follow-ups + tally',
                'format' => 'number',
                'adjustable' => true,
                'href' => null,
            ],
            [
                'key' => 'presentation_invitations',
                'label' => 'Invitations',
                'subtitle' => 'Presentation invitations',
                'format' => 'number',
                'adjustable' => true,
                'href' => null,
            ],
            [
                'key' => 'presentations',
                'label' => 'Presentations',
                'subtitle' => 'Delivered presentations + tally',
                'format' => 'number',
                'adjustable' => true,
                'href' => null,
            ],
        ];

        if ($this->canLogFnaStarted($user)) {
            $definitions[] = [
                'key' => 'fna_started',
                'label' => 'FNA started',
                'subtitle' => 'Manual tally (licensed)',
                'format' => 'number',
                'adjustable' => true,
                'href' => null,
            ];
        }

        return array_map(function (array $definition) use ($help): array {
            $metricHelp = $help[$definition['key']] ?? [];

            return [
                ...$definition,
                'label' => $metricHelp['label'] ?? $definition['label'],
                'description' => $metricHelp['description'] ?? null,
                'examples' => array_values($metricHelp['examples'] ?? []),
            ];
        }, $definitions);
    }

    private function defaultTarget(string $key, string $periodType): float
    {
        $weeklyDefaults = [
            'contacts' => 25,
            'followups_completed' => 15,
            'presentation_invitations' => 10,
            'presentations' => 3,
            'fna_started' => 2,
            'new_prospects' => 10,
            'monthly_premium' => 5000,
            'recruits' => 0.5,
        ];

        $base = (float) ($weeklyDefaults[$key] ?? 5);

        return match ($periodType) {
            self::PERIOD_DAILY => round($base / 5, 1),
            self::PERIOD_MONTHLY => round($base * 4.33, 1),
            default => $base,
        };
    }
}
