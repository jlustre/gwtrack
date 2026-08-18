<?php

namespace Tests\Feature;

use App\Jobs\Notifications\DispatchCalendarRemindersJob;
use App\Jobs\Notifications\EvaluateNotificationEscalationsJob;
use App\Jobs\Notifications\SendNotificationDigestsJob;
use App\Livewire\Notifications\CriticalAlertBanner;
use App\Models\CalendarEvent;
use App\Models\CalendarEventReminder;
use App\Models\MentorAssignment;
use App\Models\Notification;
use App\Models\Prospect;
use App\Models\ProspectFollowUp;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskUserAttributes;
use App\Services\Notifications\NotificationDigestService;
use App\Services\Notifications\NotificationEscalationService;
use App\Services\Notifications\NotificationOrchestrator;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
        ]);
    }

    public function test_trainee_inactivity_escalation_notifies_cfm_at_threshold(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create([
            'last_login_at' => now()->subDays(8),
            'mentor_id' => $cfm->id,
        ]);
        $trainee->assignRole('member');

        MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'assigned_by' => $cfm->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        app(NotificationEscalationService::class)->evaluateAll();

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $cfm->id)
                ->where('data->trigger', 'trainee_inactivity_cfm')
                ->exists()
        );
    }

    public function test_overdue_task_escalation_notifies_assignee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->seed(TaskCategorySeeder::class);

        TaskUser::query()->create(TaskUserAttributes::forCategoryTask('Licensing', [
            'assignee_id' => $user->id,
            'assignor_id' => $user->id,
            'priority' => 'high',
            'status' => 'to_do',
            'due_date' => now()->subDay()->toDateString(),
        ]));

        app(NotificationEscalationService::class)->evaluateAll();

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $user->id)
                ->where('data->trigger', 'task_overdue')
                ->exists()
        );
    }

    public function test_prospect_follow_up_overdue_escalation_notifies_owner(): void
    {
        $this->seed(ProspectLookupSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $stageId = DB::table('pipeline_stages')->value('id');
        $sourceId = DB::table('prospect_sources')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $owner->id,
            'prospect_source_id' => $sourceId,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Overdue',
            'last_name' => 'Prospect',
            'email' => 'overdue@example.com',
            'status' => 'active',
            'is_archived' => false,
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        ProspectFollowUp::query()->create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $owner->id,
            'due_at' => now()->subDay(),
            'followup_type' => 'no_contact_7d',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        app(NotificationEscalationService::class)->evaluateAll();

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $owner->id)
                ->where('data->trigger', 'prospect_follow_up_overdue')
                ->exists()
        );
    }

    public function test_calendar_reminder_job_dispatches_notification(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('member');

        $event = CalendarEvent::query()->create([
            'organizer_id' => $organizer->id,
            'title' => 'Team huddle',
            'starts_at' => now()->addMinutes(10),
            'ends_at' => now()->addHour(),
            'timezone' => 'UTC',
            'visibility' => 'private',
            'status' => 'scheduled',
            'color' => '#84BB41',
        ]);

        CalendarEventReminder::query()->create([
            'calendar_event_id' => $event->id,
            'user_id' => $organizer->id,
            'minutes_before' => 15,
            'channel' => 'in_app',
        ]);

        app(DispatchCalendarRemindersJob::class)->handle(app(\App\Services\Notifications\CalendarReminderDispatcher::class));

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $organizer->id)
                ->where('data->trigger', 'calendar_event_reminder')
                ->exists()
        );
    }

    public function test_daily_digest_sends_email_when_content_exists(): void
    {
        $user = User::factory()->create(['email' => 'digest@example.com']);
        $user->assignRole('member');

        app(NotificationOrchestrator::class)->deliver([
            'queue' => false,
            'trigger_code' => 'task_overdue',
            'recipients' => [$user->id],
            'priority' => 'high',
            'title' => 'Digest test notification',
            'message' => 'Something needs attention.',
        ]);

        config(['notifications.digest.daily.default_time' => '00:00']);

        $this->assertTrue(app(NotificationDigestService::class)->sendDigestIfDue($user, 'daily'));
    }

    public function test_critical_alert_banner_shows_urgent_notifications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('view notifications');

        app(NotificationOrchestrator::class)->deliver([
            'queue' => false,
            'trigger_code' => 'trainee_inactivity_risk',
            'recipients' => [$user->id],
            'priority' => 'critical',
            'title' => 'Critical test alert',
            'message' => 'Immediate action required.',
        ]);

        Livewire::actingAs($user)
            ->test(CriticalAlertBanner::class)
            ->assertSee('Critical test alert')
            ->assertSee('Immediate action required');
    }

    public function test_escalation_job_runs_without_error(): void
    {
        $this->expectNotToPerformAssertions();

        app(EvaluateNotificationEscalationsJob::class)->handle(app(NotificationEscalationService::class));
    }

    public function test_digest_job_runs_without_error(): void
    {
        $this->expectNotToPerformAssertions();

        $job = new SendNotificationDigestsJob('daily');
        $job->handle(app(NotificationDigestService::class));
    }
}
