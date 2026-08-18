<?php

use App\Jobs\Fna\RollupFnaAnalytics;
use App\Jobs\CleanupOldBackupsJob;
use App\Jobs\CfmEffectiveness\RollupCfmEffectivenessScores;
use App\Jobs\CfmEffectiveness\SyncCfmMilestoneDayReviews;
use App\Jobs\DispatchComplianceRenewalReminders;
use App\Jobs\Goals\DispatchGoalReminders;
use App\Jobs\Goals\GenerateGoalScorecards;
use App\Jobs\Goals\RollupGoalProgress;
use App\Jobs\Goals\SendGoalPerformanceReports;
use App\Jobs\Notifications\GenerateNotificationInsightsJob;
use App\Jobs\Notifications\DispatchCalendarRemindersJob;
use App\Jobs\Notifications\EvaluateNotificationEscalationsJob;
use App\Jobs\Notifications\SendNotificationDigestsJob;
use App\Jobs\Support\CheckSupportTicketSlaJob;
use App\Services\Calendar\PublicHolidayCatalog;
use App\Services\Calendar\PublicHolidaySyncService;
use Illuminate\Support\Facades\Cache;
use App\Jobs\Prospects\RollupProspectAnalytics;
use App\Jobs\Prospects\RunProspectFollowUpEngine;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('calendar:sync-public-holidays', function (PublicHolidaySyncService $sync, PublicHolidayCatalog $catalog): void {
    $years = $catalog->yearsForRollingWindow();

    $sync->syncYears($years);

    Cache::put('public-holidays.synced-through.'.max($years), true, now()->addDay());

    $this->info('Synced public holidays for years: '.implode(', ', $years));
})->purpose('Generate public holiday calendar events for the configured year window');

Schedule::job(RunProspectFollowUpEngine::class)->hourly();
Schedule::job(RollupProspectAnalytics::class)->daily();
Schedule::job(RollupFnaAnalytics::class)->daily();

Schedule::job(RollupGoalProgress::class)->daily();
Schedule::job(DispatchGoalReminders::class)->hourly();
Schedule::job(GenerateGoalScorecards::class)->weeklyOn(1, '06:00');
Schedule::job(new SendGoalPerformanceReports('weekly'))->weeklyOn(1, '08:00');
Schedule::job(new SendGoalPerformanceReports('monthly'))->monthlyOn(1, '08:00');
Schedule::job(new SendGoalPerformanceReports('quarterly'))->cron('0 8 1 1,4,7,10 *');
Schedule::job(CheckSupportTicketSlaJob::class)->hourly();

Schedule::job(EvaluateNotificationEscalationsJob::class)->dailyAt('06:00');
Schedule::job(DispatchCalendarRemindersJob::class)->everyFifteenMinutes();
Schedule::job(new SendNotificationDigestsJob('daily'))->hourly();
Schedule::job(new SendNotificationDigestsJob('weekly'))->hourly();
Schedule::job(GenerateNotificationInsightsJob::class)->dailyAt('05:30');
Schedule::job(SyncCfmMilestoneDayReviews::class)->dailyAt('06:30');
Schedule::job(RollupCfmEffectivenessScores::class)->dailyAt('06:45');
Schedule::job(new RollupCfmEffectivenessScores('monthly'))->monthlyOn(1, '07:00');
Schedule::job(DispatchComplianceRenewalReminders::class)->dailyAt('07:15');
Schedule::job(CleanupOldBackupsJob::class)->dailyAt('02:30');

Schedule::command('calendar:sync-public-holidays')->monthlyOn(1, '03:15');
