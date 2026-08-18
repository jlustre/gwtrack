<?php

namespace App\Jobs\Prospects;

use App\Models\User;
use App\Services\Prospects\ProspectAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RollupProspectAnalytics implements ShouldQueue
{
    use Queueable;

    public function handle(ProspectAnalyticsService $analytics): void
    {
        $userIds = DB::table('prospects')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('owner_id');

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $analytics->writeDailySnapshots($user);
            $analytics->refreshGoalActuals($user);
        }
    }
}
