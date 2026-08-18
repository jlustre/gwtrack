<?php

namespace App\Jobs\Goals;

use App\Models\Goal;
use App\Models\User;
use App\Services\Goals\GoalReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGoalPerformanceReports implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $periodType = 'weekly',
    ) {}

    public function handle(GoalReportService $reports): void
    {
        $userIds = Goal::query()->distinct()->pluck('user_id');

        User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->select('id', 'email', 'name')
            ->chunkById(100, function ($users) use ($reports): void {
                foreach ($users as $user) {
                    $reports->sendEmail($user, $this->periodType);
                }
            });
    }
}
