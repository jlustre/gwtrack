<?php

namespace Tests\Feature\Fna;

use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FnaFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_associate_can_create_fna_draft_via_service(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($user, ['client_name' => 'Test Client']);

        $this->assertDatabaseHas('fna_records', [
            'id' => $fna->id,
            'owner_user_id' => $user->id,
            'status' => 'draft',
            'client_name' => 'Test Client',
        ]);

        $this->assertDatabaseHas('fna_households', ['fna_record_id' => $fna->id]);
        $this->assertDatabaseHas('fna_dime_analyses', ['fna_record_id' => $fna->id]);
    }

    public function test_fna_dashboard_requires_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $this->actingAs($user)
            ->get(route('team.fna.dashboard'))
            ->assertOk()
            ->assertSee('FNA Management');
    }

    public function test_owner_can_view_own_fna_but_not_another_users_draft(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $other = User::factory()->create();
        $other->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Private Client']);

        $this->actingAs($owner)
            ->get(route('team.fna.show', $fna))
            ->assertOk()
            ->assertSee('Private Client');

        $this->actingAs($other)
            ->get(route('team.fna.show', $fna))
            ->assertForbidden();
    }

    public function test_cfm_can_view_trainee_submitted_fna_but_not_draft(): void
    {
        $this->seed(RolePermissionSeeder::class);

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

        $draft = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Draft Client']);

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
            ->assertSee('Draft Client');
    }

    public function test_create_fna_via_http_redirects_to_wizard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->post(route('team.fna.store'), ['client_name' => 'HTTP Client'])
            ->assertRedirect()
            ->assertSessionHas('fna_status');

        $this->assertDatabaseHas('fna_records', [
            'owner_user_id' => $user->id,
            'client_name' => 'HTTP Client',
            'status' => 'draft',
        ]);
    }
}
