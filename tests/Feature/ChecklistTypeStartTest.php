<?php

namespace Tests\Feature;

use App\Models\MentorAssignment;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class ChecklistTypeStartTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    public function test_member_sees_not_started_page_until_ao_starts_checklist(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Onboarding')
            ->assertSee('Licensing Tracker')
            ->assertSee('Contracting Tracker')
            ->assertSee('Field Apprenticeship')
            ->assertSee('CFM Training');

        $this->actingAs($member)
            ->get(route('onboarding.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Start checklist')
            ->assertDontSee('Overall Progress');
    }

    public function test_member_can_start_own_onboarding_checklist(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        $this->actingAs($member)
            ->post(route('checklists.type.start', 'onboarding'), [
                'started_at' => '2026-04-15',
            ])
            ->assertRedirect(route('onboarding.index'));

        $this->assertDatabaseHas('user_checklist_type_starts', [
            'user_id' => $member->id,
            'started_at' => '2026-04-15 00:00:00',
            'started_by' => $member->id,
        ]);

        $this->actingAs($member)
            ->get(route('onboarding.index'))
            ->assertOk()
            ->assertSee('My Onboarding')
            ->assertDontSee('Not started yet');
    }

    public function test_member_can_start_own_licensing_and_fap_checklists(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        foreach (['licensing' => 'licensing.index', 'fap' => 'apprenticeship.index'] as $typeCode => $routeName) {
            $this->actingAs($member)
                ->post(route('checklists.type.start', $typeCode), [
                    'started_at' => '2026-05-01',
                ])
                ->assertRedirect(route($routeName));

            $this->actingAs($member)
                ->get(route($routeName))
                ->assertOk()
                ->assertDontSee('Not started yet');
        }
    }

    public function test_member_cannot_start_cfm_training_without_prerequisites(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        $this->actingAs($member)
            ->get(route('cfm-training.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Prerequisites required')
            ->assertDontSee('Start checklist');

        $this->actingAs($member)
            ->from(route('cfm-training.index'))
            ->post(route('checklists.type.start', 'cfm-training'), [
                'started_at' => '2026-04-15',
            ])
            ->assertRedirect(route('cfm-training.index'))
            ->assertSessionHasErrors('type');
    }

    public function test_member_cannot_start_contracting_without_licensing_complete(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        $this->actingAs($member)
            ->get(route('contracting.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Prerequisites required')
            ->assertDontSee('Start checklist');

        $this->actingAs($member)
            ->from(route('contracting.index'))
            ->post(route('checklists.type.start', 'contracting'), [
                'started_at' => '2026-04-15',
            ])
            ->assertRedirect(route('contracting.index'))
            ->assertSessionHasErrors('type');
    }

    public function test_member_cannot_start_contracting_until_licensed(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        foreach (['onboarding', 'licensing'] as $typeCode) {
            $this->startChecklistType($member, $typeCode);
            $this->completeChecklistType($member, $typeCode);
        }

        $this->actingAs($member)
            ->get(route('contracting.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Life license required')
            ->assertDontSee('Start checklist');

        $this->actingAs($member)
            ->from(route('contracting.index'))
            ->post(route('checklists.type.start', 'contracting'), [
                'started_at' => '2026-06-01',
            ])
            ->assertRedirect(route('contracting.index'))
            ->assertSessionHasErrors('type');
    }

    public function test_member_can_start_contracting_when_licensed_and_prerequisites_are_complete(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();
        $this->markLicensed($member);

        foreach (['onboarding', 'licensing'] as $typeCode) {
            $this->startChecklistType($member, $typeCode);
            $this->completeChecklistType($member, $typeCode);
        }

        $this->actingAs($member)
            ->get(route('contracting.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Start checklist');

        $this->actingAs($member)
            ->post(route('checklists.type.start', 'contracting'), [
                'started_at' => '2026-06-01',
            ])
            ->assertRedirect(route('contracting.index'));

        $this->actingAs($member)
            ->get(route('contracting.index'))
            ->assertOk()
            ->assertSee('Contracting Checklist')
            ->assertDontSee('Not started yet');
    }

    public function test_member_can_start_cfm_training_when_onboarding_licensing_and_fap_are_complete(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = $this->member();

        foreach (['onboarding', 'licensing', 'fap'] as $typeCode) {
            $this->startChecklistType($member, $typeCode);
            $this->completeChecklistType($member, $typeCode);
        }

        $this->actingAs($member)
            ->get(route('cfm-training.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Start checklist');

        $this->actingAs($member)
            ->post(route('checklists.type.start', 'cfm-training'), [
                'started_at' => '2026-06-01',
            ])
            ->assertRedirect(route('cfm-training.index'));

        $this->assertDatabaseHas('user_checklist_type_starts', [
            'user_id' => $member->id,
            'started_at' => '2026-06-01 00:00:00',
            'started_by' => $member->id,
        ]);

        $this->actingAs($member)
            ->get(route('cfm-training.index'))
            ->assertOk()
            ->assertSee('CFM Training Checklist')
            ->assertDontSee('Not started yet');
    }

    public function test_ao_can_start_onboarding_with_explicit_day_one_date(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $ao = User::factory()->create();
        $ao->assignRole('agency-owner');

        $member = $this->member(['sponsor_id' => $ao->id]);

        $this->actingAs($ao)
            ->post(route('team.member.checklist-type.start', ['user' => $member, 'typeCode' => 'onboarding']), [
                'started_at' => '2026-04-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_checklist_type_starts', [
            'user_id' => $member->id,
            'started_at' => '2026-04-15 00:00:00',
            'started_by' => $ao->id,
        ]);

        $this->actingAs($member)
            ->get(route('onboarding.index'))
            ->assertOk()
            ->assertSee('My Onboarding')
            ->assertDontSee('Not started yet');
    }

    public function test_cfm_can_start_checklist_for_assigned_trainee(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $member = $this->member(['mentor_id' => $cfm->id]);

        MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $member->id,
            'assigned_by' => $cfm->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->actingAs($cfm)
            ->post(route('team.member.checklist-type.start', ['user' => $member, 'typeCode' => 'licensing']), [
                'started_at' => '2026-05-01',
            ])
            ->assertRedirect();

        $this->actingAs($member)
            ->get(route('licensing.index'))
            ->assertOk()
            ->assertSee('Licensing Tracker')
            ->assertDontSee('Not started yet');
    }

    public function test_super_admin_can_start_own_licensing_from_tracker_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $admin->profile()->create(LocationOptions::profileLocationIds('Canada'));

        $this->actingAs($admin)
            ->get(route('licensing.index'))
            ->assertOk()
            ->assertSee('Not started yet')
            ->assertSee('Start checklist');

        $this->actingAs($admin)
            ->post(route('team.member.checklist-type.start', ['user' => $admin, 'typeCode' => 'licensing']), [
                'started_at' => '2026-06-01',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('licensing.index'))
            ->assertOk()
            ->assertSee('Licensing Checklist')
            ->assertDontSee('Not started yet');
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('member');
        $user->profile()->create(array_merge([
            'is_gw_active_associate' => true,
            'gw_associate_id' => 'GW-START-'.$user->id,
        ], LocationOptions::profileLocationIds('Canada')));

        return $user;
    }

    private function markLicensed(User $user): void
    {
        $user->profile->update([
            'license_number' => 'LIC-'.$user->id,
            'is_licensed_life_insurance' => true,
            'insurance_licenses' => ['Canada|Ontario'],
        ]);
    }
}
