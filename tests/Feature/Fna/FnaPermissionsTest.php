<?php

namespace Tests\Feature\Fna;

use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\Fna\FnaExportService;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FnaPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function createTraineeWithCfm(): array
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create();
        $trainee->assignRole('associate');

        MentorAssignment::create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        return [$trainee, $cfm];
    }

    public function test_cfm_can_view_submitted_fna_but_not_trainee_draft(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$trainee, $cfm] = $this->createTraineeWithCfm();

        $draft = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Hidden Draft']);

        $this->actingAs($cfm)
            ->get(route('team.fna.show', $draft))
            ->assertForbidden();

        $submitted = FnaRecord::find($draft->id);
        $submitted->update([
            'status' => 'submitted_to_cfm',
            'cfm_user_id' => $cfm->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($cfm)
            ->get(route('team.fna.show', $submitted))
            ->assertOk()
            ->assertSee('Hidden Draft');
    }

    public function test_owner_cannot_view_another_users_draft(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $other = User::factory()->create();
        $other->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Private Draft']);

        $this->actingAs($other)
            ->get(route('team.fna.show', $fna))
            ->assertForbidden();
    }

    public function test_export_requires_permission_and_record_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $other = User::factory()->create();
        $other->assignRole('trainer');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Export Permission Client']);

        $this->actingAs($owner)
            ->get(route('team.fna.export', $fna))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('team.fna.export', $fna))
            ->assertForbidden();
    }

    public function test_financial_details_policy_blocks_unauthorized_viewer(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Role::findByName('associate')->revokePermissionTo('view fna financial details');

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Masked Client']);
        $fna->incomeDetail()->update(['annual_income' => 120000]);

        $this->assertFalse(Gate::forUser($owner)->allows('viewFinancialDetails', $fna));

        $data = app(FnaExportService::class)->buildExportData($fna, $owner);

        $this->assertFalse($data['can_view_financial']);
        $this->assertTrue($data['income']['restricted']);
    }
}
