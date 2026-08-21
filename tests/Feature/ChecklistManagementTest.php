<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistType;
use App\Models\User;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChecklistManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_seeder_populates_all_types(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $this->assertDatabaseHas('checklists', [
            'title' => 'Complete Member Profile',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('checklists', [
            'title' => 'Pass Licensing Exam',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('checklists', [
            'title' => 'Select Target Carriers',
            'is_active' => true,
        ]);

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $this->assertGreaterThan(0, Checklist::query()->where('checklist_type_id', $onboardingTypeId)->count());

        $mentoringTypeId = ChecklistType::query()->where('code', 'cfm-mentoring')->value('id');
        $this->assertGreaterThan(0, Checklist::query()->where('checklist_type_id', $mentoringTypeId)->count());
    }

    public function test_admin_can_filter_checklists_by_type(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $licensingTypeId = ChecklistType::query()->where('code', 'licensing')->value('id');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', ['checklists', 'checklist_type' => $onboardingTypeId]))
            ->assertOk()
            ->assertSee('Complete Member Profile')
            ->assertDontSee('Pass Licensing Exam');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', ['checklists', 'checklist_type' => $licensingTypeId]))
            ->assertOk()
            ->assertSee('Pass Licensing Exam')
            ->assertDontSee('Complete Member Profile');
    }

    public function test_admin_checklist_page_shows_quick_type_filter_buttons_with_persistent_results(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $fapTypeId = ChecklistType::query()->where('code', 'fap')->value('id');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'checklists'))
            ->assertOk()
            ->assertSee('Checklist Type', false)
            ->assertSee('Onboarding', false)
            ->assertSee('Licensing', false)
            ->assertSee('Contracting', false)
            ->assertSee('FAP', false)
            ->assertSee('CFM Mentoring', false)
            ->assertSee('CFM Training', false);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', ['checklists', 'checklist_type' => $onboardingTypeId]))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('Complete Member Profile')
            ->assertDontSee('Pass Licensing Exam');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', [
                'checklists',
                'checklist_type' => $onboardingTypeId,
                'search' => 'Profile',
            ]))
            ->assertOk()
            ->assertSee('Complete Member Profile')
            ->assertSee('checklist_type='.$onboardingTypeId, false);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', ['checklists', 'checklist_type' => $fapTypeId]))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee('Complete Member Profile');
    }

    public function test_admin_can_update_checklist_seeder_from_list(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seederPath = database_path('seeders/ChecklistSeeder.php');
        $phasesPath = database_path('seeders/data/cfm_mentoring_phases.php');
        $originalSeeder = file_get_contents($seederPath);
        $originalPhases = file_get_contents($phasesPath);

        $licensingTypeId = ChecklistType::query()->where('code', 'licensing')->value('id');

        Checklist::query()->create([
            'checklist_type_id' => $licensingTypeId,
            'title' => 'Seed Export Test Step',
            'description' => 'Exported from admin checklist management.',
            'sort_order' => 9990,
            'is_required' => true,
            'responsible_parties' => 'Self, SP',
            'notified_parties' => 'SP',
            'is_active' => true,
        ]);

        try {
            $this->actingAs($admin)
                ->get(route('admin.management.resource.index', 'checklists'))
                ->assertOk()
                ->assertSee('Update Seeder');

            $this->actingAs($admin)
                ->post(route('admin.management.update-seeder', 'checklists'))
                ->assertRedirect(route('admin.management.resource.index', 'checklists'))
                ->assertSessionHas('status', 'seeder-updated');

            $seeder = file_get_contents($seederPath);

            $this->assertStringContainsString('Seed Export Test Step', $seeder);
            $this->assertStringContainsString('Exported from admin checklist management.', $seeder);
            $this->assertStringContainsString("upsertItem('licensing'", $seeder);
        } finally {
            file_put_contents($seederPath, $originalSeeder);
            file_put_contents($phasesPath, $originalPhases);
        }
    }

    public function test_admin_can_manage_checklists(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $typeId = ChecklistType::query()->where('code', 'licensing')->value('id');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', [
                'checklists',
                'checklist_type' => $onboardingTypeId,
            ]))
            ->assertOk()
            ->assertSee('Checklists')
            ->assertSee('Back to Checklists')
            ->assertSee(route('admin.checklists.index'), false)
            ->assertSee('Complete Member Profile')
            ->assertSee('Confirmation Needed', false)
            ->assertSee('Needed', false)
            ->assertSee('editOpen = true', false)
            ->assertSee('Edit Record', false);

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'checklists'), [
                'checklist_type_id' => $typeId,
                'title' => 'Custom Licensing Step',
                'description' => 'A custom licensing milestone.',
                'sort_order' => 999,
                'is_required' => 1,
                'requires_confirmation' => 0,
                'responsible_parties' => 'Self, SP',
                'notified_parties' => 'SP',
                'country' => '',
                'group_label' => '',
                'phase_number' => '',
                'phase_title' => '',
                'phase_target' => '',
                'section_title' => '',
                'slug' => '',
                'action_url' => '',
                'action_label' => '',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Custom Licensing Step',
            'is_active' => true,
            'requires_confirmation' => false,
        ]);
    }

    public function test_updating_checklist_preserves_selected_type_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');
        $stepId = DB::table('checklists')
            ->where('checklist_type_id', $onboardingTypeId)
            ->whereNull('deleted_at')
            ->value('id');

        $this->assertNotNull($stepId);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', [
                'checklists',
                'checklist_type' => $onboardingTypeId,
            ]))
            ->assertOk()
            ->assertSee(
                'admin/management/checklists/'.$stepId.'?checklist_type='.$onboardingTypeId,
                false,
            );

        $this->actingAs($admin)
            ->patch(route('admin.management.update', [
                'checklists',
                $stepId,
                'checklist_type' => $onboardingTypeId,
            ]), [
                'checklist_type_id' => $onboardingTypeId,
                'title' => 'Updated Onboarding Step',
                'description' => 'Kept under the Onboarding filter.',
                'sort_order' => 10,
                'is_required' => 1,
                'requires_confirmation' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.management.resource.index', [
                'checklists',
                'checklist_type' => $onboardingTypeId,
            ]));

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', [
                'checklists',
                'checklist_type' => $onboardingTypeId,
            ]))
            ->assertOk()
            ->assertSee('Updated Onboarding Step')
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('Showing', false)
            ->assertSee('Onboarding', false);
    }

    public function test_checklist_without_confirmation_auto_approves_on_complete(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);

        $typeId = ChecklistType::query()->where('code', 'onboarding')->value('id')
            ?? ChecklistType::query()->create([
                'code' => 'onboarding',
                'name' => 'Onboarding',
                'sort_order' => 10,
                'is_active' => true,
            ])->id;

        $autoItem = Checklist::query()->create([
            'checklist_type_id' => $typeId,
            'title' => 'Auto Approve Step',
            'description' => 'No CFM confirmation needed.',
            'sort_order' => 1,
            'is_required' => true,
            'requires_confirmation' => false,
            'is_active' => true,
        ]);

        $confirmItem = Checklist::query()->create([
            'checklist_type_id' => $typeId,
            'title' => 'Needs Confirmation Step',
            'description' => 'CFM must approve.',
            'sort_order' => 2,
            'is_required' => true,
            'requires_confirmation' => true,
            'notified_parties' => 'CFM',
            'is_active' => true,
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');

        $service = app(\App\Services\ChecklistService::class);
        $service->updateUserProgress($member, $autoItem->id, true);
        $service->updateUserProgress($member, $confirmItem->id, true);

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $member->id,
            'checklist_id' => $autoItem->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $member->id,
            'checklist_id' => $confirmItem->id,
            'status' => 'pending_confirmation',
        ]);
    }

    public function test_checklists_hub_links_to_checklist_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.checklists.index'))
            ->assertOk()
            ->assertSee('Checklist Items')
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.management.resource.index', 'checklists'), '/').'"[^>]*\bwire:navigate\b/',
            $html,
        );
    }
}
