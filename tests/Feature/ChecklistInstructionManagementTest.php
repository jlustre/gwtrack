<?php

namespace Tests\Feature;

use App\Models\ChecklistInstruction;
use App\Models\ChecklistType;
use App\Models\User;
use Database\Seeders\ChecklistInstructionSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistInstructionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_instruction_seeder_populates_sample_guides(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistInstructionSeeder::class);

        $onboarding = ChecklistType::query()->where('code', 'onboarding')->firstOrFail();

        $this->assertDatabaseHas('checklist_instructions', [
            'checklist_type_id' => $onboarding->id,
            'doc_link' => '/resources/documents/welcome-packet',
            'is_active' => true,
        ]);

        $instruction = ChecklistInstruction::query()
            ->where('checklist_type_id', $onboarding->id)
            ->firstOrFail();

        $this->assertStringContainsString('Welcome to onboarding', $instruction->instructions);
        $this->assertGreaterThanOrEqual(5, ChecklistInstruction::query()->count());
    }

    public function test_admin_can_manage_checklist_instructions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = ChecklistType::query()->where('code', 'training')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'checklist-instructions'))
            ->assertOk()
            ->assertSee('Checklist Instructions');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'checklist-instructions'), [
                'checklist_type_id' => $type->id,
                'instructions' => '<p><strong>Training tips</strong></p><ul><li>Watch each lesson before marking complete.</li></ul>',
                'doc_link' => '/resources/documents/training-manual',
                'other_link' => 'https://example.com/training-faq',
                'sort_order' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_instructions', [
            'checklist_type_id' => $type->id,
            'doc_link' => '/resources/documents/training-manual',
            'other_link' => 'https://example.com/training-faq',
        ]);
    }

    public function test_checklists_hub_links_to_checklist_instructions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.checklists.index'))
            ->assertOk()
            ->assertSee('Checklist Instructions')
            ->assertSee(route('admin.management.resource.index', 'checklist-instructions'), false);
    }
}
