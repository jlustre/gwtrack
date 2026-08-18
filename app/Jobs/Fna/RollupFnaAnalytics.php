<?php

namespace App\Jobs\Fna;

use App\Models\User;
use App\Services\Fna\FnaAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RollupFnaAnalytics implements ShouldQueue
{
    use Queueable;

    public function handle(FnaAnalyticsService $analytics): void
    {
        $userIds = DB::table('fna_records')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('owner_user_id');

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $analytics->rollupForUser($user);
        }
    }
}
