<?php

namespace Tests\Feature\Prospects;

use App\Models\User;
use Database\Seeders\ProspectDashboardTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectDashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_prospect_dashboard_renders_with_seeded_data_and_action_links(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtracks.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Prospect Management', false)
            ->assertSee('All Prospects', false)
            ->assertSee('Quick navigation', false)
            ->assertSee('Kanban Test Hot', false)
            ->assertSee('Overdue Followup Frank', false)
            ->assertSee('Create Prospect', false)
            ->assertSee(route('team.prospects.pipeline'), false)
            ->assertDontSee(route('team.prospects.create'), false)
            ->assertSee(route('team.prospects.analytics'), false);
    }

    public function test_stat_card_hot_filter_link_works(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtracks.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('team.prospects', ['prospect_interest' => 'hot']))
            ->assertOk()
            ->assertSee('Kanban Test Hot', false)
            ->assertDontSee('Import Duplicate Test', false);
    }
}
