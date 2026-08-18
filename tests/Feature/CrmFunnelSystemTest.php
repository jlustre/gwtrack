<?php

namespace Tests\Feature;

use App\Livewire\Admin\Crm\PipelineManager;
use App\Livewire\Crm\ConsultantTracker;
use App\Livewire\Crm\FunnelDashboard;
use App\Livewire\Crm\PipelineComparison;
use App\Models\CrmFunnelParticipation;
use App\Models\CrmPipeline;
use App\Models\Profile;
use App\Models\User;
use App\Services\Crm\CrmFunnelService;
use Database\Seeders\CrmFunnelSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CrmFunnelSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_dashboard_and_nav_are_available(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CRM Funnel System', false)
            ->assertSee('Consultant Tracker', false);

        $this->actingAs($user)
            ->get(route('crm.index'))
            ->assertOk()
            ->assertSeeLivewire(FunnelDashboard::class)
            ->assertSee('Lead and Prospect Funnel', false)
            ->assertSee('Sales Funnel', false)
            ->assertSee('Recruiting Funnel', false)
            ->assertSee('Licensing Funnel', false);
    }

    public function test_pipelines_are_independent_for_same_member(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $owner = User::factory()->create();
        $owner->assignRole('member');
        $member = User::factory()->create(['sponsor_id' => $owner->id]);
        $member->assignRole('member');

        $service = app(CrmFunnelService::class);
        $sales = CrmPipeline::query()->where('key', 'sales')->firstOrFail();
        $licensing = CrmPipeline::query()->where('key', 'licensing')->firstOrFail();

        $service->enroll($member, $sales, owner: $owner, actor: $owner);
        $service->enroll($member, $licensing, owner: $owner, actor: $owner);

        $this->assertSame(2, CrmFunnelParticipation::query()->where('user_id', $member->id)->count());

        $overview = $service->consultantOverview($member);
        $enrolled = collect($overview['pipelines'])->where('enrolled', true)->pluck('key')->sort()->values()->all();

        $this->assertSame(['licensing', 'sales'], $enrolled);
    }

    public function test_consultant_tracker_can_select_member_and_enroll(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $owner = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $owner->assignRole('member');
        Profile::query()->create(['user_id' => $owner->id]);

        $member = User::factory()->create([
            'sponsor_id' => $owner->id,
            'name' => 'Pipeline Member',
            'is_active' => true,
        ]);
        $member->assignRole('member');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $owner->id, 'descendant_id' => $owner->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $owner->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $member->id, 'descendant_id' => $member->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($owner)
            ->test(ConsultantTracker::class)
            ->set('q', 'Pipeline Member')
            ->call('selectMember', $member->id)
            ->assertSet('memberId', $member->id)
            ->call('enroll', 'onboarding')
            ->assertSee('Onboarding Funnel', false);

        $this->assertDatabaseHas('crm_funnel_participations', [
            'user_id' => $member->id,
            'crm_pipeline_id' => CrmPipeline::query()->where('key', 'onboarding')->value('id'),
            'state' => 'active',
        ]);
    }

    public function test_admin_can_manage_pipeline_stages(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pipeline = CrmPipeline::query()->where('key', 'fap')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(PipelineManager::class)
            ->call('selectPipeline', $pipeline->id)
            ->set('stageName', 'Custom FAP Checkpoint')
            ->set('stageIsTerminal', false)
            ->call('addStage')
            ->assertSee('Custom FAP Checkpoint', false);

        $this->assertDatabaseHas('crm_pipeline_stages', [
            'crm_pipeline_id' => $pipeline->id,
            'name' => 'Custom FAP Checkpoint',
            'slug' => 'custom-fap-checkpoint',
        ]);
    }

    public function test_admin_can_delete_pipeline_stage(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pipeline = CrmPipeline::query()->where('key', 'fap')->firstOrFail();
        $stage = $pipeline->stages()->where('name', 'FAP Enrolled')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(PipelineManager::class)
            ->call('selectPipeline', $pipeline->id)
            ->call('deleteStage', $stage->id)
            ->assertSet('statusMessage', 'stage-deleted');

        $this->assertSoftDeleted('crm_pipeline_stages', [
            'id' => $stage->id,
        ]);

        $orders = $pipeline->stages()->orderBy('sort_order')->pluck('sort_order')->map(fn ($order) => (int) $order)->all();
        $this->assertSame(range(10, count($orders) * 10, 10), $orders);
    }

    public function test_admin_can_update_crm_funnel_seeder_from_database(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pipeline = CrmPipeline::query()->where('key', 'sales')->firstOrFail();
        $stage = $pipeline->stages()->where('name', 'Financial Needs Analysis')->firstOrFail();
        $stage->forceFill([
            'description' => 'Custom FNA description for seeder export test.',
        ])->save();

        $configPath = config_path('crm-funnels.php');
        $original = file_get_contents($configPath);

        try {
            Livewire::actingAs($admin)
                ->test(PipelineManager::class)
                ->call('updateSeeder')
                ->assertSet('statusMessage', 'seeder-updated');

            $updated = file_get_contents($configPath);
            $this->assertStringContainsString('Custom FNA description for seeder export test.', $updated);
            $this->assertStringContainsString("'key' => 'sales'", $updated);
        } finally {
            file_put_contents($configPath, $original);
        }
    }

    public function test_non_admin_cannot_update_crm_funnel_seeder(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        Livewire::actingAs($leader)
            ->test(PipelineManager::class)
            ->assertDontSee('Update Seeder', false)
            ->call('updateSeeder')
            ->assertForbidden();
    }

    public function test_crm_funnel_seeder_persists_stage_descriptions(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $sales = CrmPipeline::query()->where('key', 'sales')->firstOrFail();
        $stage = $sales->stages()->where('name', 'Financial Needs Analysis')->firstOrFail();

        $this->assertNotEmpty($stage->description);
        $this->assertStringContainsString('needs analysis', strtolower($stage->description));
    }

    public function test_pipelines_and_stages_comparison_is_read_only(): void
    {
        $this->seed([RolePermissionSeeder::class, CrmFunnelSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        $sales = CrmPipeline::query()->where('key', 'sales')->firstOrFail();
        $licensing = CrmPipeline::query()->where('key', 'licensing')->firstOrFail();

        $this->actingAs($user)
            ->get(route('crm.pipelines.compare'))
            ->assertOk()
            ->assertSeeLivewire(PipelineComparison::class)
            ->assertSee('Compare funnel stages side by side', false)
            ->assertDontSee('Add Stage', false);

        Livewire::actingAs($user)
            ->test(PipelineComparison::class)
            ->call('setColumnPipeline', 0, $sales->id)
            ->call('setColumnPipeline', 1, $licensing->id)
            ->assertSee($sales->name, false)
            ->assertSee($licensing->name, false)
            ->call('addColumn')
            ->assertCount('columnPipelineIds', 3)
            ->call('removeColumn', 2)
            ->assertCount('columnPipelineIds', 2);
    }
}
