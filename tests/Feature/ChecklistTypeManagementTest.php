<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistType;
use App\Models\User;
use App\Services\ChecklistService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChecklistTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_type_seeder_populates_default_types(): void
    {
        $this->seed(ChecklistTypeSeeder::class);

        $this->assertDatabaseHas('checklist_types', [
            'code' => 'onboarding',
            'name' => 'Onboarding',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('checklist_types', [
            'code' => 'cfm-mentoring',
            'name' => 'CFM Mentoring',
        ]);

        $this->assertSame(7, ChecklistType::query()->count());
    }

    public function test_admin_can_manage_checklist_types(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'checklist-types'))
            ->assertOk()
            ->assertSee('Checklist Types')
            ->assertSee('Update Seeder')
            ->assertSee('onboarding')
            ->assertSee('CFM Mentoring');

        $onboardingId = ChecklistType::query()->where('code', 'onboarding')->value('id');

        $this->actingAs($admin)
            ->get(route('admin.management.edit', ['checklist-types', $onboardingId]))
            ->assertOk()
            ->assertSee('Edit Checklist Type');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'checklist-types'), [
                'code' => 'custom-type',
                'name' => 'Custom Checklist',
                'description' => 'A custom checklist category.',
                'icon' => 'star',
                'sort_order' => 99,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_types', [
            'code' => 'custom-type',
            'name' => 'Custom Checklist',
            'sort_order' => 99,
        ]);
    }

    public function test_checklists_hub_links_to_checklist_types(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.checklists.index'))
            ->assertOk()
            ->assertSee('Checklist Types')
            ->assertSee(route('admin.management.resource.index', 'checklist-types'), false);
    }

    public function test_admin_can_update_checklist_type_seeder_from_list(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seederPath = database_path('seeders/ChecklistTypeSeeder.php');
        $originalSeeder = file_get_contents($seederPath);

        ChecklistType::query()
            ->where('code', 'licensing')
            ->update([
                'max_complete_days' => 120,
                'description' => 'Exported licensing description from admin.',
            ]);

        try {
            $this->actingAs($admin)
                ->post(route('admin.management.update-seeder', 'checklist-types'))
                ->assertRedirect(route('admin.management.resource.index', 'checklist-types'))
                ->assertSessionHas('status', 'seeder-updated');

            $seeder = file_get_contents($seederPath);

            $this->assertStringContainsString('Exported licensing description from admin.', $seeder);
            $this->assertStringContainsString('max_complete_days', $seeder);
            $this->assertStringContainsString('120', $seeder);
            $this->assertStringContainsString('prerequisite_codes', $seeder);
        } finally {
            file_put_contents($seederPath, $originalSeeder);
        }
    }

    public function test_admin_can_set_multiple_checklist_type_prerequisites(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $onboardingId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $licensingId = ChecklistType::query()->where('code', 'licensing')->value('id');
        $fapId = ChecklistType::query()->where('code', 'fap')->value('id');

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['checklist-types', $fapId]), [
                'code' => 'fap',
                'name' => 'Field Apprenticeship Program',
                'description' => 'Field Apprenticeship Program milestones for associates and trainees.',
                'icon' => 'academic',
                'sort_order' => 30,
                'max_complete_days' => '',
                'prerequisite_checklist_type_ids' => [$onboardingId, $licensingId],
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_type_prerequisites', [
            'checklist_type_id' => $fapId,
            'prerequisite_checklist_type_id' => $onboardingId,
        ]);

        $this->assertDatabaseHas('checklist_type_prerequisites', [
            'checklist_type_id' => $fapId,
            'prerequisite_checklist_type_id' => $licensingId,
        ]);

        $this->assertSame(2, DB::table('checklist_type_prerequisites')->where('checklist_type_id', $fapId)->count());
    }

    public function test_prerequisite_met_requires_all_prerequisite_types_completed(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $onboardingId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $licensingId = ChecklistType::query()->where('code', 'licensing')->value('id');
        $fapId = ChecklistType::query()->where('code', 'fap')->value('id');

        ChecklistType::query()->whereKey($fapId)->firstOrFail()->prerequisites()->sync([
            $onboardingId,
            $licensingId,
        ]);

        $user = User::factory()->create(['joined_at' => now()]);
        $service = app(ChecklistService::class);

        $this->assertFalse($service->prerequisiteMet($user, 'fap'));

        $onboardingChecklistIds = Checklist::query()->where('checklist_type_id', $onboardingId)->pluck('id');

        foreach ($onboardingChecklistIds as $checklistId) {
            \App\Models\ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'checklist_id' => $checklistId,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                ],
            );
        }

        $this->assertFalse($service->prerequisiteMet($user, 'fap'));

        $licensingChecklistIds = Checklist::query()->where('checklist_type_id', $licensingId)->pluck('id');

        foreach ($licensingChecklistIds as $checklistId) {
            \App\Models\ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'checklist_id' => $checklistId,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                ],
            );
        }

        $this->assertTrue($service->prerequisiteMet($user, 'fap'));
    }
}
