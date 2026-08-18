<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectActivitiesModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Prospect $prospect;

    private int $contactedStageId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('member');

        $insuranceFunnelId = (int) DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $newLeadStageId = (int) DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $contactedStageId = (int) DB::table('pipeline_stages')->where('slug', 'contacted')->value('id');

        $this->prospect = Prospect::create([
            'owner_id' => $this->user->id,
            'prospect_funnel_id' => $insuranceFunnelId,
            'pipeline_stage_id' => $newLeadStageId,
            'first_name' => 'Stage',
            'last_name' => 'Modal',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        $this->contactedStageId = $contactedStageId;
    }

    public function test_activities_index_returns_pipeline_stages_for_prospect_funnel(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('team.prospects.activities.index', $this->prospect));

        $response->assertOk()
            ->assertJsonPath('current_pipeline_stage_id', $this->prospect->pipeline_stage_id)
            ->assertJsonStructure([
                'activities',
                'pipeline_stages' => [['id', 'name']],
                'current_pipeline_stage_name',
                'activity_types',
                'form_fields',
                'outcome_suggestions',
            ]);

        $stages = collect($response->json('pipeline_stages'));

        $this->assertGreaterThan(1, $stages->count());
        $this->assertSame('1. New Lead', $stages->first()['label']);
        $this->assertNotEmpty($response->json('activity_types'));
        $this->assertNotEmpty($response->json('form_fields'));
    }

    public function test_store_activity_with_duration_minutes(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('team.prospects.activities.store', $this->prospect), [
                'activity_type' => 'zoom_meeting',
                'occurred_at' => now()->toIso8601String(),
                'duration_minutes' => 45,
                'outcome' => 'Completed',
            ]);

        $response->assertCreated()
            ->assertJsonPath('activity.duration_minutes', 45);

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $this->prospect->id,
            'activity_type' => 'zoom_meeting',
            'duration_minutes' => 45,
        ]);
    }

    public function test_store_activity_updates_pipeline_stage_and_records_metadata(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('team.prospects.activities.store', $this->prospect), [
                'activity_type' => 'phone_call',
                'occurred_at' => now()->toIso8601String(),
                'outcome' => 'Connected',
                'notes' => 'Moved to contacted after call.',
                'pipeline_stage_id' => $this->contactedStageId,
            ]);

        $response->assertCreated()
            ->assertJsonPath('current_pipeline_stage_id', $this->contactedStageId)
            ->assertJsonPath('activity.pipeline_stage_id', $this->contactedStageId);

        $this->assertDatabaseHas('prospects', [
            'id' => $this->prospect->id,
            'pipeline_stage_id' => $this->contactedStageId,
        ]);

        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $this->prospect->id,
            'to_stage_id' => $this->contactedStageId,
            'change_source' => 'activity_log',
        ]);
    }
}
