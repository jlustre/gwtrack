<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectAnalytics;
use App\Models\Prospect;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Database\Seeders\DownlineManagementSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectAnalyticsScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);
    }

    public function test_team_aggregates_exclude_pii_and_scope_to_hierarchy(): void
    {
        $owner = User::where('email', 'downline-owner@gwtrack.com')->firstOrFail();
        $owner->forceFill(['email_verified_at' => now()])->save();
        $member = User::where('sponsor_id', $owner->id)->firstOrFail();
        $outsider = User::factory()->create();
        $outsider->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        Prospect::create([
            'owner_id' => $member->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Secret',
            'last_name' => 'ProspectName',
            'interest_level' => 'hot',
            'priority' => 'high',
        ]);

        Prospect::create([
            'owner_id' => $outsider->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Outside',
            'last_name' => 'Prospect',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($owner)
            ->get(route('team.prospects.analytics'));

        $response->assertOk()
            ->assertSee('Team Aggregates')
            ->assertSee('Team Prospects')
            ->assertDontSee('Secret ProspectName')
            ->assertDontSee('Outside Prospect');

        Livewire::actingAs($owner)
            ->test(ProspectAnalytics::class)
            ->assertSee('team members in scope');
    }

    public function test_associate_without_downline_does_not_see_team_panel(): void
    {
        $associate = User::factory()->create();
        $associate->assignRole('member');

        $this->actingAs($associate)
            ->get(route('team.prospects.analytics'))
            ->assertOk()
            ->assertDontSee('Team Aggregates');
    }
}
