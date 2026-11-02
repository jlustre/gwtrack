<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectDemoSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_is_populated_with_prospect_data(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'referral')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'funnel_type' => 'insurance',
            'first_name' => 'Jordan',
            'last_name' => 'Lee',
            'email' => 'jordan.lee@example.com',
            'phone' => '555-123-4567',
            'city' => 'Toronto',
            'status' => 'active',
            'interest_level' => 'hot',
            'priority' => 'high',
            'referral_source_name' => 'Church event',
            'campaign_name' => 'Spring 2026',
            'qualification_notes' => 'Very interested in mortgage protection.',
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects.records.edit', $prospect))
            ->assertOk()
            ->assertSee('Edit Prospect')
            ->assertSee('Jordan Lee')
            ->assertSee('value="Jordan"', false)
            ->assertSee('jordan.lee@example.com', false)
            ->assertSee('value="Toronto"', false)
            ->assertSee('Very interested in mortgage protection.');
    }

    public function test_edit_form_works_without_prospect_funnel_seed_data(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtracks.com')->firstOrFail();
        $prospect = Prospect::where('owner_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.prospects.records.edit', $prospect))
            ->assertOk()
            ->assertSee('Edit Prospect')
            ->assertSee($prospect->displayName())
            ->assertSee('value="'.$prospect->first_name.'"', false);
    }

    public function test_edit_form_can_save_changes(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Jordan',
            'last_name' => 'Lee',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)
            ->patch(route('team.prospects.records.update', $prospect), [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'funnel_type' => 'insurance',
                'prospect_funnel_id' => $funnelId,
                'status' => 'active',
                'interest_level' => 'hot',
                'priority' => 'high',
            ])
            ->assertRedirect(route('team.prospects.records.show', $prospect));

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'priority' => 'high',
        ]);
    }
}
