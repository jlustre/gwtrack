<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\CfmCoachingSession;
use App\Models\CfmNotification;
use App\Models\MentorAssignment;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPhaseSixTest extends TestCase
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

    public function test_cfm_can_view_ai_coaching_assistant_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'assistant')
            ->assertSee('AI Coaching Assistant')
            ->assertSee('Generate brief')
            ->assertSee('Ask the assistant');
    }

    public function test_cfm_can_generate_coaching_brief_and_ask_questions(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'assistant')
            ->set('aiFocusArea', 'risk')
            ->call('generateCoachingBrief');

        $session = CfmCoachingSession::query()->where('trainee_id', $assignment->apprentice_id)->firstOrFail();
        $this->assertSame('risk', $session->focus_area);
        $this->assertNotEmpty($session->notes);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'assistant')
            ->set('aiQuestion', 'What is the current risk level?')
            ->call('askAssistant')
            ->assertSet('aiAnswer', fn (string $answer) => str_contains(strtolower($answer), 'risk'));
    }

    public function test_cfm_can_send_sms_notification_to_trainee(): void
    {
        $this->seedPortal();
        Config::set('cfm-portal.sms.enabled', true);

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);
        $trainee = User::query()->findOrFail($assignment->apprentice_id);

        Profile::query()->updateOrCreate(
            ['user_id' => $trainee->id],
            ['phone' => '6045550100'],
        );

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'assistant')
            ->set('smsTemplate', 'check_in')
            ->call('sendSmsToTrainee');

        $this->assertDatabaseHas('cfm_notifications', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'channel' => 'sms',
            'template' => 'check_in',
        ]);
    }

    public function test_sms_notification_fails_without_trainee_phone(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);
        $trainee = User::query()->with('profile')->findOrFail($assignment->apprentice_id);

        if ($trainee->profile) {
            $trainee->profile->update(['phone' => null]);
        }

        $this->assertNull($trainee->fresh('profile')->profile?->phone);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'assistant')
            ->call('sendSmsToTrainee')
            ->assertStatus(422);
    }

    public function test_dashboard_shows_live_ai_priorities(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->assertSee('AI Coaching Assistant')
            ->assertSee('Live');
    }
}
