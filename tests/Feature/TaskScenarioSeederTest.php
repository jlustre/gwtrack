<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskManagementSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_scenario_seeder_creates_demo_task_records(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            TaskManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'sofia.needsmentor@example.com',
            'sponsor_id' => $agencyOwner->id,
            'mentor_id' => null,
        ]);

        $this->assertSame(1, $this->pendingProgressCount('onboarding'));
        $this->assertSame(1, $this->pendingProgressCount('licensing'));
        $this->assertSame(1, $this->pendingProgressCount('fap'));
        $this->assertSame(1, $this->pendingProgressCount('cfm-training'));
        $this->assertSame(2, DB::table('registration_invitations')->where('sponsor_id', $agencyOwner->id)->whereNull('last_emailed_at')->count());
        $this->assertSame(1, DB::table('user_rank_progress')->where('status', 'ready_for_review')->count());

        $this->actingAs($agencyOwner)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Pass Licensing Exam')
            ->assertSee('Receive FAP Approval')
            ->assertSee('CFM Certification Review')
            ->assertSee('Assign a CFM to Sofia Reyes')
            ->assertSee('prospect.one@example.com')
            ->assertSee('Rank advancement')
            ->assertSee('Manage users');
    }

    private function pendingProgressCount(string $typeCode): int
    {
        return DB::table('checklist_progress')
            ->join('checklists', 'checklists.id', '=', 'checklist_progress.checklist_id')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', $typeCode)
            ->where('checklist_progress.status', 'pending_confirmation')
            ->whereNull('checklist_progress.mentor_assignment_id')
            ->count();
    }
}
