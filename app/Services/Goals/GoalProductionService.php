<?php

namespace App\Services\Goals;

use App\Models\MemberProductionEntry;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\MemberProfileTabsService;
use Carbon\Carbon;

class GoalProductionService
{
    public function __construct(
        private readonly MemberProfileTabsService $memberProfileTabs,
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function totalForUser(User $user, Carbon $start, Carbon $end): float
    {
        $sum = MemberProductionEntry::query()
            ->where('user_id', $user->id)
            ->where('status', 'posted')
            ->whereDate('posted_at', '>=', $start)
            ->whereDate('posted_at', '<=', $end)
            ->sum('annual_premium');

        if ($sum > 0) {
            return (float) $sum;
        }

        return $this->fallbackProduction($user, $start, $end);
    }

    public function teamTotalForUser(User $viewer, Carbon $start, Carbon $end): float
    {
        $memberIds = $this->hierarchy->dashboardMembersQuery($viewer)->pluck('id');

        if ($memberIds->isEmpty()) {
            return $this->totalForUser($viewer, $start, $end);
        }

        $sum = MemberProductionEntry::query()
            ->whereIn('user_id', $memberIds)
            ->where('status', 'posted')
            ->whereDate('posted_at', '>=', $start)
            ->whereDate('posted_at', '<=', $end)
            ->sum('annual_premium');

        if ($sum > 0) {
            return (float) $sum;
        }

        return $memberIds->sum(fn (int $id) => $this->fallbackProduction(
            User::query()->find($id) ?? $viewer,
            $start,
            $end,
        ));
    }

    public function entryCountForUser(User $user, Carbon $start, Carbon $end): int
    {
        $count = MemberProductionEntry::query()
            ->where('user_id', $user->id)
            ->where('status', 'posted')
            ->whereDate('posted_at', '>=', $start)
            ->whereDate('posted_at', '<=', $end)
            ->count();

        if ($count > 0) {
            return $count;
        }

        return $this->memberProfileTabs->annualPremiumTotal($user) > 0 ? 1 : 0;
    }

    private function fallbackProduction(User $user, Carbon $start, Carbon $end): float
    {
        $annualTotal = (float) $this->memberProfileTabs->annualPremiumTotal($user);

        if ($annualTotal <= 0) {
            return 0;
        }

        $yearStart = $start->copy()->startOfYear();
        $yearEnd = $start->copy()->endOfYear();
        $daysInYear = max(1, $yearStart->diffInDays($yearEnd) + 1);
        $rangeDays = max(1, $start->diffInDays($end) + 1);

        return round(($annualTotal / $daysInYear) * $rangeDays, 2);
    }
}
