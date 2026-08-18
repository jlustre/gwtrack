<?php

namespace Tests\Feature\Goals;

use App\Mail\GoalPerformanceReportMail;
use App\Jobs\Goals\DispatchGoalReminders;
use App\Jobs\Goals\RollupGoalProgress;
use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\GoalCategory;
use App\Models\GoalReminder;
use App\Models\MemberProductionEntry;
use App\Models\Notification;
use App\Models\User;
use App\Services\Goals\GoalAchievementService;
use App\Services\Goals\GoalHierarchyRollupService;
use App\Services\Goals\GoalProductionService;
use App\Services\Goals\GoalReminderService;
use App\Services\Goals\GoalReportService;
use App\Services\Goals\GoalScorecardService;
use Database\Seeders\GoalBadgeSeeder;
use Database\Seeders\GoalCategorySeeder;
use Database\Seeders\GoalTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
            GoalCategorySeeder::class,
            GoalTemplateSeeder::class,
            GoalBadgeSeeder::class,
        ]);
    }

    public function test_hierarchy_rollup_aggregates_child_goals(): void
    {
        $user = User::factory()->create();
        $categoryId = GoalCategory::query()->where('slug', 'production')->value('id');

        $parent = Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Monthly production',
            'measurement_type' => 'currency',
            'target_value' => 10000,
            'actual_value' => 0,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'parent_goal_id' => $parent->id,
            'hierarchy_level' => 'weekly',
            'name' => 'Week 1',
            'measurement_type' => 'currency',
            'target_value' => 2500,
            'actual_value' => 2000,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'parent_goal_id' => $parent->id,
            'hierarchy_level' => 'weekly',
            'name' => 'Week 2',
            'measurement_type' => 'currency',
            'target_value' => 2500,
            'actual_value' => 1500,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        app(GoalHierarchyRollupService::class)->rollupForUser($user);

        $parent->refresh();
        $this->assertEquals(3500, (float) $parent->actual_value);
    }

    public function test_production_service_uses_member_production_entries(): void
    {
        $user = User::factory()->create();

        MemberProductionEntry::query()->create([
            'user_id' => $user->id,
            'annual_premium' => 5000,
            'posted_at' => now()->toDateString(),
            'status' => 'posted',
        ]);

        $total = app(GoalProductionService::class)->totalForUser(
            $user,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        $this->assertEquals(5000.0, $total);
    }

    public function test_due_reminder_sends_notification(): void
    {
        $user = User::factory()->create();
        $categoryId = GoalCategory::query()->value('id');

        $goal = Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Test goal',
            'measurement_type' => 'number',
            'target_value' => 10,
            'actual_value' => 2,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        GoalReminder::query()->create([
            'goal_id' => $goal->id,
            'user_id' => $user->id,
            'remind_at' => now()->subMinute(),
            'channel' => 'in_app',
            'message' => 'Check in on your goal',
            'is_active' => true,
        ]);

        app(GoalReminderService::class)->processDueReminders();

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $user->id)
                ->where('data->trigger', 'goal_reminder')
                ->exists()
        );
    }

    public function test_scorecard_generation_persists_record(): void
    {
        $user = User::factory()->create();
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Scorecard goal',
            'measurement_type' => 'number',
            'target_value' => 10,
            'actual_value' => 5,
            'status' => 'active',
            'starts_at' => now()->startOfWeek(),
            'deadline_at' => now()->endOfWeek(),
        ]);

        $scorecard = app(GoalScorecardService::class)->generateForUser(
            $user,
            'weekly',
            now()->startOfWeek(),
            now()->endOfWeek(),
        );

        $this->assertDatabaseHas('goal_scorecards', [
            'id' => $scorecard->id,
            'user_id' => $user->id,
            'period_type' => 'weekly',
        ]);
        $this->assertGreaterThan(0, $scorecard->overall_score);
    }

    public function test_report_service_builds_preview_data(): void
    {
        $user = User::factory()->create();
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Report goal',
            'measurement_type' => 'number',
            'target_value' => 10,
            'actual_value' => 8,
            'status' => 'active',
            'starts_at' => now()->subMonth()->startOfMonth(),
            'deadline_at' => now()->subMonth()->endOfMonth(),
        ]);

        $data = app(GoalReportService::class)->buildReportData($user, 'monthly');

        $this->assertSame('Monthly', $data['period_label']);
        $this->assertGreaterThanOrEqual(1, $data['goals']->count());
    }

    public function test_reports_page_renders_for_member(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('goals.reports'))
            ->assertRedirect(route('goals.index', ['tab' => 'reports']));

        $this->actingAs($user)
            ->get(route('goals.index', ['tab' => 'reports']))
            ->assertOk()
            ->assertSee('Performance Reports', false)
            ->assertSee(route('goals.reports.download', ['period' => 'weekly']), false);
    }

    public function test_reports_pdf_download_returns_pdf(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Report goal',
            'measurement_type' => 'number',
            'target_value' => 10,
            'actual_value' => 5,
            'status' => 'active',
            'starts_at' => now()->subWeek()->startOfWeek(),
            'deadline_at' => now()->subWeek()->endOfWeek(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('goals.reports.download', ['period' => 'weekly']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_reports_email_sends_mail_and_redirects(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->post(route('goals.reports.email'), ['period' => 'monthly'])
            ->assertRedirect(route('goals.index', ['tab' => 'reports']))
            ->assertSessionHas('goals_status');

        Mail::assertSent(GoalPerformanceReportMail::class, fn (GoalPerformanceReportMail $mail) => $mail->hasTo($user->email));
    }

    public function test_goal_index_list_allows_edit_and_delete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $categoryId = GoalCategory::query()->value('id');

        $goal = Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Editable goal',
            'measurement_type' => 'number',
            'target_value' => 10,
            'actual_value' => 2,
            'status' => 'active',
            'starts_at' => now(),
            'deadline_at' => now()->addMonth(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Goals\GoalIndex::class)
            ->call('setViewMode', 'list')
            ->call('editGoal', $goal->id)
            ->set('editName', 'Updated goal name')
            ->set('editTargetValue', '20')
            ->set('editActualValue', '5')
            ->call('saveGoal')
            ->assertSet('editingGoalId', null);

        $goal->refresh();
        $this->assertSame('Updated goal name', $goal->name);
        $this->assertSame('20.00', $goal->target_value);
        $this->assertSame('5.00', $goal->actual_value);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Goals\GoalIndex::class)
            ->call('setViewMode', 'list')
            ->call('deleteGoal', $goal->id);

        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_goal_index_supports_timeline_view_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Timeline goal',
            'measurement_type' => 'number',
            'target_value' => 5,
            'actual_value' => 1,
            'status' => 'active',
            'starts_at' => now(),
            'deadline_at' => now()->addMonth(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Goals\GoalIndex::class)
            ->call('setViewMode', 'timeline')
            ->assertSee('Timeline goal', false);
    }

    public function test_rollup_job_runs_without_error(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Job goal',
            'measurement_type' => 'number',
            'metric_key' => 'contacts',
            'target_value' => 10,
            'actual_value' => 0,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        RollupGoalProgress::dispatchSync();
        DispatchGoalReminders::dispatchSync();

        $this->assertTrue(true);
    }
}
