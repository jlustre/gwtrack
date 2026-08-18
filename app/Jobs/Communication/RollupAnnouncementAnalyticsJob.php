<?php

namespace App\Jobs\Communication;

use App\Services\Communication\AnnouncementAnalyticsService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RollupAnnouncementAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $date = null,
    ) {}

    public function handle(AnnouncementAnalyticsService $analytics): void
    {
        $date = $this->date ? Carbon::parse($this->date) : now()->subDay();
        $analytics->rollupForDate($date);
    }
}
