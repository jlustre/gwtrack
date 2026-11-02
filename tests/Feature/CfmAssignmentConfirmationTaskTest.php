<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskController;
use App\Models\MentorAssignment;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\CfmAssignmentConfirmationTaskService;
use App\Services\CfmAssignmentWorkflowService;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\NotificationModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemTaskAssignorSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CfmAssignmentConfirmationTaskTest extends TestCase
{
    use RefreshDatabase;

    private function seedWorld(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationModuleSeeder::class,
            NotificationConfigSeeder::class,
            SystemTaskAssignorSeeder::class,
            TaskCategorySeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);
    }

    public function test_pending_assignment_creates_cfm_confirmation_task_with_review_link(): void
    {
        Mail::fake();
        $this->seedWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $cfm->forceFill(['email_verified_at' => now()])->save();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $associate->id,
            'assigned_by' => $agencyOwner->id,
            'status' => 'pending',
            'started_at' => now()->toDateString(),
        ]);

        app(CfmAssignmentWorkflowService::class)->sendConfirmationRequest($assignment->fresh(['mentor', 'apprentice.sponsor', 'assignedBy']));

        $task = TaskUser::query()
            ->where('assignee_id', $cfm->id)
            ->where('related_person', $associate->name)
            ->where('related_module', CfmAssignmentConfirmationTaskService::RELATED_MODULE)
            ->first();

        $this->assertNotNull($task);
        $this->assertSame('to_do', $task->status);

        $mapped = app(TaskController::class)->openTasksByPriorityFor($cfm);
        $storedTask = collect($mapped['items'])->first(
            fn (array $item): bool => str_contains(strtolower($item['title'] ?? ''), 'confirm trainee assignment')
        );

        $this->assertNotNull($storedTask);
        $this->assertSame('Confirm', $storedTask['action_label']);
        $this->assertStringContainsString('/cfm/portal/assignments/'.$assignment->id.'/review', $storedTask['url']);
    }

    public function test_cfm_can_review_and_confirm_assignment_from_task_link(): void
    {
        Mail::fake();
        $this->seedWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $cfm->forceFill(['email_verified_at' => now()])->save();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $associate->id,
            'assigned_by' => $agencyOwner->id,
            'status' => 'pending',
            'started_at' => now()->toDateString(),
        ]);

        app(CfmAssignmentWorkflowService::class)->sendConfirmationRequest($assignment->fresh(['mentor', 'apprentice.sponsor', 'assignedBy']));

        $task = TaskUser::query()
            ->where('assignee_id', $cfm->id)
            ->where('related_module', CfmAssignmentConfirmationTaskService::RELATED_MODULE)
            ->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('cfm.portal.assignments.review', $assignment))
            ->assertOk()
            ->assertSee('Confirm your new trainee', false)
            ->assertSee($associate->name, false)
            ->assertSee('Confirm assignment', false);

        $this->actingAs($cfm)
            ->post(route('cfm.portal.assignments.confirm', $assignment))
            ->assertRedirect(route('cfm.portal'));

        $task->refresh();
        $associate->refresh();
        $assignment->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertSame(100, $task->progress);
        $this->assertSame($cfm->id, $associate->mentor_id);
        $this->assertSame('active', $assignment->status);
    }
}
