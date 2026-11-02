<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\CfmMeeting;
use App\Models\CfmNotification;
use App\Models\CfmProgressReport;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    private function seedPortal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);
    }

    private function activeAssignmentForCfm(User $cfm): MentorAssignment
    {
        return MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function test_cfm_can_view_meetings_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'meetings')
            ->assertSee('Meetings & Sessions')
            ->assertSee('Schedule meeting');
    }

    public function test_cfm_can_schedule_meeting_and_save_notes(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'meetings')
            ->set('meetingTitle', 'FAP weekly review')
            ->set('meetingType', 'fap_review')
            ->set('meetingStartsAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('createMeeting');

        $meeting = CfmMeeting::query()->where('trainee_id', $assignment->apprentice_id)->firstOrFail();
        $this->assertSame('FAP weekly review', $meeting->title);
        $this->assertSame('scheduled', $meeting->status);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'meetings')
            ->call('selectMeetingForNotes', $meeting->id)
            ->set('meetingNoteSummary', 'Reviewed prospecting pipeline and next steps.')
            ->set('meetingActionItems', "Schedule 5 FNA calls\nComplete licensing module 2")
            ->call('saveMeetingNotes');

        $this->assertDatabaseHas('cfm_meeting_notes', [
            'cfm_meeting_id' => $meeting->id,
            'summary' => 'Reviewed prospecting pipeline and next steps.',
        ]);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'meetings')
            ->call('updateMeetingStatus', $meeting->id, 'completed');

        $this->assertDatabaseHas('cfm_meetings', [
            'id' => $meeting->id,
            'status' => 'completed',
        ]);
    }

    public function test_cfm_can_view_reports_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'reports')
            ->assertSee('Progress Reports')
            ->assertSee('Generate report')
            ->assertSee('Live preview');
    }

    public function test_cfm_can_generate_report_notify_trainee_and_download_pdf(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'reports')
            ->set('reportType', 'progress_snapshot')
            ->set('reportAudience', 'trainee')
            ->set('reportNotifyTrainee', true)
            ->call('generateReport');

        $report = CfmProgressReport::query()->where('trainee_id', $assignment->apprentice_id)->firstOrFail();
        $this->assertSame('progress_snapshot', $report->report_type);
        $this->assertIsArray($report->payload);
        $this->assertArrayHasKey('progress', $report->payload);

        $this->assertDatabaseHas('cfm_notifications', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'template' => 'progress_report',
        ]);

        $this->assertNotNull(CfmNotification::query()->where('trainee_id', $assignment->apprentice_id)->value('sent_at'));

        $response = $this->actingAs($cfm)
            ->get(route('cfm.portal.reports.download', $report));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_cfm_cannot_access_trainee_meeting(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        $meeting = CfmMeeting::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'title' => 'Private session',
            'type' => 'coaching',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);

        $otherCfm = User::factory()->create();
        $otherCfm->assignRole('certified-field-mentor');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($otherCfm)
            ->test(Portal::class)
            ->call('deleteMeeting', $meeting->id);
    }
}
