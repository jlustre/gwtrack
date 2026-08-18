<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectExportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_export_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('certified-field-mentor');

        $this->actingAs($user)
            ->get(route('team.prospects.export'))
            ->assertForbidden();
    }

    public function test_export_streams_only_owner_prospects(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'first_name' => 'Export',
            'last_name' => 'Mine',
            'email' => 'mine@example.com',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        $other = User::factory()->create();
        $other->assignRole('member');

        Prospect::create([
            'owner_id' => $other->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Other',
            'last_name' => 'User',
            'email' => 'other@example.com',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('team.prospects.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('mine@example.com', $content);
        $this->assertStringContainsString('Export', $content);
        $this->assertStringNotContainsString('other@example.com', $content);
    }

    public function test_dashboard_shows_export_button_for_authorized_users(): void
    {
        $this->actingAs($this->owner)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Export CSV');
    }
}
