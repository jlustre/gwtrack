<?php

namespace Tests\Feature;

use App\Models\TrainingModule;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Training\TrainingCoursePlayerService;
use App\Services\Training\TrainingReportService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('member');
    }

    public function test_member_can_view_training_reports_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('training.reports.index'))
            ->assertOk()
            ->assertSeeText('Training Reports & Analytics');

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\TrainingReports::class)
            ->assertSee('Lessons completed')
            ->assertSee('My course progress');
    }

    public function test_personal_report_counts_lessons_completed_in_period(): void
    {
        Carbon::setTestNow('2026-06-12 10:00:00');

        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        Carbon::setTestNow('2026-06-17 10:00:00');

        $report = app(TrainingReportService::class)->buildReportData($this->user, 'weekly', 'personal');

        $this->assertGreaterThanOrEqual(1, $report['summary']['lessons_completed']);
        $this->assertNotEmpty($report['course_rows']);

        Carbon::setTestNow();
    }

    public function test_leader_can_view_downline_scope_report(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $trainee = User::factory()->create(['sponsor_id' => $leader->id]);
        $trainee->assignRole('member');

        app(DownlineHierarchyService::class)->rebuild();

        Carbon::setTestNow('2026-06-12 10:00:00');

        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $trainee,
            $module->load('lessons'),
            $lesson,
        );

        Carbon::setTestNow('2026-06-17 10:00:00');

        $report = app(TrainingReportService::class)->buildReportData($leader, 'weekly', 'downline');

        $this->assertGreaterThanOrEqual(1, $report['summary']['lessons_completed']);
        $this->assertNotEmpty($report['member_rows']);

        Carbon::setTestNow();
    }

    public function test_member_cannot_access_organization_scope(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(TrainingReportService::class)->buildReportData($this->user, 'monthly', 'organization');
    }

    public function test_member_can_download_personal_report_pdf(): void
    {
        $this->actingAs($this->user)
            ->get(route('training.reports.download', ['period' => 'monthly', 'scope' => 'personal']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_member_can_email_personal_report(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('training.reports.email'), [
                'period' => 'monthly',
                'scope' => 'personal',
            ])
            ->assertRedirect(route('training.reports.index'));

        Mail::assertSent(\App\Mail\TrainingReportMail::class, function ($mail): bool {
            return $mail->hasTo($this->user->email);
        });
    }

    public function test_admin_can_use_organization_scope(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $scopes = app(TrainingReportService::class)->availableScopesFor($admin);

        $this->assertContains('organization', $scopes);

        $report = app(TrainingReportService::class)->buildReportData($admin, 'monthly', 'organization');

        $this->assertSame('Organization', $report['scope_label']);
    }
}
