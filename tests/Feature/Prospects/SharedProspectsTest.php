<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SharedProspectsTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);
    }

    private function makeProspect(User $owner): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        return Prospect::create([
            'owner_id' => $owner->id,
            'prospect_source_id' => $sourceId,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Shared',
            'last_name' => 'Prospect',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);
    }

    public function test_shared_with_me_route_renders_active_shares(): void
    {
        $this->seedBase();

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $sharedUser = User::factory()->create();
        $sharedUser->assignRole('member');

        $prospect = $this->makeProspect($owner);
        $permissionId = DB::table('prospect_share_permissions')->where('key', 'view_only')->value('id');

        DB::table('prospect_shares')->insert([
            'prospect_id' => $prospect->id,
            'granted_by' => $owner->id,
            'shared_with' => $sharedUser->id,
            'prospect_share_permission_id' => $permissionId,
            'permission_level' => 'view_only',
            'granted_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($sharedUser)
            ->followingRedirects()
            ->get(route('team.prospects.shared-with-me'))
            ->assertOk()
            ->assertSee('Shared With Me')
            ->assertSee('Shared Prospect')
            ->assertSee('View Profile');
    }

    public function test_shared_by_me_route_renders_granted_shares(): void
    {
        $this->seedBase();

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $collaborator = User::factory()->create();
        $collaborator->assignRole('member');

        $prospect = $this->makeProspect($owner);
        $permissionId = DB::table('prospect_share_permissions')->where('key', 'add_notes')->value('id');

        DB::table('prospect_shares')->insert([
            'prospect_id' => $prospect->id,
            'granted_by' => $owner->id,
            'shared_with' => $collaborator->id,
            'prospect_share_permission_id' => $permissionId,
            'permission_level' => 'add_notes',
            'granted_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->followingRedirects()
            ->get(route('team.prospects.shared-by-me'))
            ->assertOk()
            ->assertSee('Shared By Me')
            ->assertSee('Shared Prospect')
            ->assertSee('Add Notes');
    }

    public function test_shared_user_can_view_prospect_profile(): void
    {
        $this->seedBase();

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $sharedUser = User::factory()->create();
        $sharedUser->assignRole('member');

        $prospect = $this->makeProspect($owner);
        $permissionId = DB::table('prospect_share_permissions')->where('key', 'view_only')->value('id');

        DB::table('prospect_shares')->insert([
            'prospect_id' => $prospect->id,
            'granted_by' => $owner->id,
            'shared_with' => $sharedUser->id,
            'prospect_share_permission_id' => $permissionId,
            'permission_level' => 'view_only',
            'granted_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($sharedUser)
            ->get(route('team.prospects.records.show', $prospect))
            ->assertOk()
            ->assertSee('Prospect Profile')
            ->assertSee('Shared Prospect');

        $this->assertDatabaseHas('prospect_access_logs', [
            'prospect_id' => $prospect->id,
            'actor_id' => $sharedUser->id,
            'action' => 'view',
        ]);
    }
}
