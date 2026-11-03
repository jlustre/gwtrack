<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectOpportunityManager;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\ProspectFunnel;
use App\Models\ProspectFunnelStage;
use App\Models\User;
use App\Services\Prospects\ProspectFunnelService;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectCrmCompletionTest extends TestCase
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

    public function test_prospect_create_and_updates_keep_contact_and_opportunity_records_synchronized(): void
    {
        $funnels = app(ProspectFunnelService::class);
        $prospect = $funnels->createProspect($this->owner, [
            'funnel_type' => 'both',
            'first_name' => 'Jordan',
            'last_name' => 'Reed',
            'email' => 'jordan.reed@example.com',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);

        $this->assertNotNull($prospect->contact_id);
        $this->assertNotNull($prospect->sales_opportunity_id);
        $this->assertNotNull($prospect->recruiting_opportunity_id);
        $this->assertDatabaseHas('contacts', [
            'id' => $prospect->contact_id,
            'email' => 'jordan.reed@example.com',
        ]);

        $sales = $prospect->salesOpportunity()->firstOrFail();
        $sales->update(['probability' => 81, 'estimated_value' => 25000]);

        $updated = $funnels->updateProspect($prospect, $this->owner, [
            'email' => 'jordan.updated@example.com',
            'interest_level' => 'hot',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $updated->contact_id,
            'email' => 'jordan.updated@example.com',
        ]);
        $this->assertSame((int) $updated->pipeline_stage_id, (int) $updated->salesOpportunity->pipeline_stage_id);
        $this->assertSame((int) $updated->pipeline_stage_id, (int) $updated->recruitingOpportunity->pipeline_stage_id);
        $this->assertSame(81, (int) $updated->salesOpportunity->probability);
        $this->assertSame('25000.00', $updated->salesOpportunity->estimated_value);
    }

    public function test_profile_can_manage_sales_and_recruiting_forecasts(): void
    {
        $prospect = $this->createProspect('both');

        Livewire::actingAs($this->owner)
            ->test(ProspectOpportunityManager::class, ['prospect' => $prospect])
            ->set('estimatedValue', '125000')
            ->set('salesProbability', '60')
            ->set('expectedCloseAt', now()->addMonths(2)->toDateString())
            ->set('recruitingProbability', '70')
            ->set('expectedStartAt', now()->addMonths(3)->toDateString())
            ->set('lostReason', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Opportunity forecast saved.');

        $this->assertDatabaseHas('sales_opportunities', [
            'prospect_id' => $prospect->id,
            'estimated_value' => '125000.00',
            'probability' => 60,
        ]);
        $this->assertDatabaseHas('recruiting_opportunities', [
            'prospect_id' => $prospect->id,
            'probability' => 70,
        ]);
    }

    public function test_invalid_cross_funnel_stage_is_rejected(): void
    {
        $prospect = $this->createProspect('insurance');
        $recruitingStage = PipelineStage::query()->create([
            'user_id' => null,
            'name' => 'Recruiting Presentation',
            'slug' => 'recruiting-presentation',
            'sort_order' => 999,
            'is_active' => true,
        ]);
        ProspectFunnelStage::query()->create([
            'prospect_funnel_id' => ProspectFunnel::query()->where('key', 'recruiting')->value('id'),
            'pipeline_stage_id' => $recruitingStage->id,
            'name' => 'Recruiting Presentation',
            'slug' => 'recruiting-presentation',
            'sort_order' => 10,
        ]);

        try {
            app(ProspectFunnelService::class)->moveStage($prospect, $this->owner, $recruitingStage->id);
            $this->fail('A stage from another funnel must not be assigned.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pipeline_stage_id', $exception->errors());
        }

        $this->assertNotSame($recruitingStage->id, (int) $prospect->fresh()->pipeline_stage_id);
    }

    public function test_conversion_state_updates_only_the_matching_opportunity(): void
    {
        $prospect = $this->createProspect('both');
        $funnels = app(ProspectFunnelService::class);

        $pending = $funnels->updateProspect($prospect, $this->owner, ['converted_to' => 'pending']);
        $this->assertSame('open', $pending->salesOpportunity->status);
        $this->assertSame('open', $pending->recruitingOpportunity->status);

        $client = $funnels->updateProspect($pending, $this->owner, [
            'converted_to' => 'client',
            'conversion_at' => now(),
        ]);
        $this->assertSame('won', $client->salesOpportunity->status);
        $this->assertSame('open', $client->recruitingOpportunity->status);
    }

    private function createProspect(string $funnelType): Prospect
    {
        return app(ProspectFunnelService::class)->createProspect($this->owner, [
            'funnel_type' => $funnelType,
            'first_name' => 'CRM',
            'last_name' => 'Test',
            'email' => 'crm-test-'.$funnelType.'@example.com',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);
    }
}
