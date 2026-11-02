<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CfmAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function seedAssignmentWorld(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);
    }

    public function test_agency_owner_assignment_sends_cfm_confirmation_email_and_stays_pending(): void
    {
        Mail::fake();
        $this->seedAssignmentWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'reason' => 'Ready for FAP mentorship',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $associate->refresh();
        $this->assertNull($associate->mentor_id);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($cfm): bool {
            return $mail->hasTo($cfm->email);
        });
    }

    public function test_cfm_confirmation_activates_trainee_and_sends_party_emails(): void
    {
        Mail::fake();
        $this->seedAssignmentWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();
        $sponsor = $associate->sponsor;

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
            ])
            ->assertOk();

        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $associate->id)
            ->where('status', 'pending')
            ->firstOrFail();

        Mail::fake();

        $this->actingAs($cfm)
            ->post(route('cfm.portal.assignments.confirm', $assignment))
            ->assertRedirect(route('cfm.portal'));

        $associate->refresh();
        $assignment->refresh();

        $this->assertSame($cfm->id, $associate->mentor_id);
        $this->assertSame('active', $assignment->status);
        $this->assertNotNull($assignment->confirmed_at);

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($associate->email));
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($cfm->email));

        if ($sponsor) {
            Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($sponsor->email));
        }
    }

    public function test_cfm_can_send_first_contact_email_to_trainee(): void
    {
        Mail::fake();
        $this->seedAssignmentWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)->postJson(route('team.cfms.assign'), [
            'associate_id' => $associate->id,
            'cfm_id' => $cfm->id,
        ]);

        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $associate->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->actingAs($cfm)->post(route('cfm.portal.assignments.confirm', $assignment));

        Mail::fake();

        $assignment->refresh();

        $this->actingAs($cfm)
            ->post(route('cfm.portal.assignments.first-contact', $assignment))
            ->assertRedirect(route('cfm.portal'));

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo($associate->email));
        $this->assertNotNull($assignment->fresh()->first_contact_sent_at);
    }

    public function test_signed_confirmation_link_works_for_logged_in_cfm(): void
    {
        Mail::fake();
        $this->seedAssignmentWorld();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtracks.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue2@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)->postJson(route('team.cfms.assign'), [
            'associate_id' => $associate->id,
            'cfm_id' => $cfm->id,
        ]);

        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $associate->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $url = URL::signedRoute('cfm.assignments.confirm', ['assignment' => $assignment->id]);

        $this->actingAs($cfm)
            ->get($url)
            ->assertRedirect(route('cfm.portal'));

        $associate->refresh();
        $this->assertSame($cfm->id, $associate->mentor_id);
    }
}
