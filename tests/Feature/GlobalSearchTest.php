<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\Prospect;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskUserAttributes;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\ResourceDocumentSeeder;
use Database\Seeders\ResourceVideoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            ResourceDocumentSeeder::class,
            ResourceVideoSeeder::class,
            TrainingAcademySeeder::class,
            TaskCategorySeeder::class,
        ]);
    }

    public function test_search_page_renders_grouped_results(): void
    {
        $user = User::factory()->create(['name' => 'Search Leader']);
        $user->assignRole('member');

        $downline = User::factory()->create([
            'name' => 'Taylor Recruit',
            'sponsor_id' => $user->id,
        ]);
        $downline->assignRole('associate');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $user->id, 'descendant_id' => $user->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $user->id, 'descendant_id' => $downline->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        Prospect::create([
            'owner_id' => $user->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Searchable',
            'last_name' => 'Prospect',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        TaskUser::query()->create(TaskUserAttributes::forTask('Prospect Follow-Up', 'Searchable follow-up task', [
            'assignee_id' => $user->id,
            'assignor_id' => $user->id,
            'status' => 'to_do',
            'priority' => 'medium',
        ]));

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee('Search GWTracks', false)
            ->assertSee('Searchable Prospect', false)
            ->assertSee('Searchable follow-up task', false);
    }

    public function test_search_suggest_endpoint_returns_json_matches(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'Prospecting']))
            ->assertOk()
            ->assertJsonStructure(['query', 'results'])
            ->assertJsonFragment(['title' => 'Prospecting Fundamentals']);
    }

    public function test_search_finds_training_courses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $course = TrainingModule::query()->published()->first();
        $this->assertNotNull($course);

        $this->actingAs($user)
            ->get(route('search.index', ['q' => $course->title, 'type' => 'training']))
            ->assertOk()
            ->assertSee($course->title, false);
    }

    public function test_search_finds_published_videos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'Welcome', 'type' => 'videos']))
            ->assertOk()
            ->assertSee('Welcome to GWTracks', false);
    }
}
