<?php

namespace Tests\Feature;

use App\Models\CfmCertificationRequest;
use App\Models\CfmMentorProfile;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmNominationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_owner_can_add_cfm_from_downline(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $candidate = User::where('email', 'owen.cfm@example.com')->firstOrFail();

        $this->assertFalse($candidate->hasRole('certified-field-mentor'));

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.store'), [
                'user_id' => $candidate->id,
                'target_rank' => 'CFM I',
                'notes' => 'Ready for mentorship duties.',
                'require_approval' => false,
            ])
            ->assertOk()
            ->assertJsonPath('certification_status', 'pending_approval');

        $candidate->refresh();
        $this->assertFalse($candidate->hasRole('certified-field-mentor'));
        $this->assertDatabaseHas('cfm_certification_requests', [
            'user_id' => $candidate->id,
            'status' => 'pending',
            'source' => 'nomination',
        ]);
        $this->assertFalse(CfmMentorProfile::where('user_id', $candidate->id)->exists());
    }

    public function test_cfm_nomination_can_require_approval(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $candidate = User::where('email', 'owen.cfm@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.store'), [
                'user_id' => $candidate->id,
                'target_rank' => 'Associate Mentor',
                'require_approval' => true,
            ])
            ->assertOk()
            ->assertJsonPath('certification_status', 'pending_approval');

        $request = CfmCertificationRequest::query()->where('user_id', $candidate->id)->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertFalse($candidate->fresh()->hasRole('certified-field-mentor'));
    }
}
