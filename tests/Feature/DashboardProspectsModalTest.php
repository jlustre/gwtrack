<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardProspectsModal;
use App\Livewire\Prospects\ProspectCreate;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardProspectsModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_prospects_quick_action_opens_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('open-dashboard-prospects-modal', false);

        Livewire::actingAs($user)
            ->test(DashboardProspectsModal::class)
            ->dispatch('open-dashboard-prospects-modal')
            ->assertSet('show', true)
            ->assertSee('Your prospect directory', false)
            ->assertSee('Prospect Summary', false);
    }

    public function test_modal_lists_prospects_with_view_and_edit_actions(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'email' => 'alex@example.com',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardProspectsModal::class)
            ->dispatch('open-dashboard-prospects-modal')
            ->assertSee('Alex Rivera')
            ->assertSee('alex@example.com')
            ->assertSee(route('team.prospects.records.show', $prospect), false)
            ->assertSee(route('team.prospects.records.edit', $prospect), false);
    }

    public function test_quick_add_from_modal_creates_prospect_and_refreshes_list(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(DashboardProspectsModal::class)
            ->dispatch('open-dashboard-prospects-modal')
            ->call('toggleQuickAdd')
            ->assertSet('showQuickAdd', true);

        Livewire::actingAs($user)
            ->test(ProspectCreate::class, ['embedded' => true, 'quick' => true, 'modal' => true])
            ->set('first_name', 'Quick')
            ->set('last_name', 'Modal')
            ->set('interest_level', 'warm')
            ->set('priority', 'medium')
            ->call('save')
            ->assertDispatched('dashboard-prospect-created');

        $this->assertDatabaseHas('prospects', [
            'owner_id' => $user->id,
            'first_name' => 'Quick',
            'last_name' => 'Modal',
        ]);
    }

    public function test_modal_search_filters_prospects(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Alpha',
            'last_name' => 'One',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Beta',
            'last_name' => 'Two',
            'status' => 'active',
            'interest_level' => 'cold',
            'priority' => 'low',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardProspectsModal::class)
            ->dispatch('open-dashboard-prospects-modal')
            ->set('search', 'Alpha')
            ->assertSee('Alpha One')
            ->assertDontSee('Beta Two');
    }
}
