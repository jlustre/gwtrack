<?php

namespace Tests\Feature\Prospects;

use App\Models\User;
use App\Services\Prospects\ProspectActivityLogSummaryService;
use Carbon\Carbon;
use Database\Seeders\ProspectDashboardTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectActivityLogSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_activity_log_summary_panel(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Prospecting activity summary', false)
            ->assertSee('Calls Attempted', false)
            ->assertSee('Clients', false)
            ->assertSee('Associates', false);
    }

    public function test_summary_service_counts_seeded_activity_metrics(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtrack.com')->firstOrFail();
        $service = app(ProspectActivityLogSummaryService::class);

        $summary = $service->summarize(
            $user,
            Carbon::now()->subDays(7)->startOfDay(),
            Carbon::now()->endOfDay(),
            'daily',
        );

        $this->assertGreaterThanOrEqual(2, $summary['totals']['phone_calls_attempted']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['contacted']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['presentations']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['invitation_success']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['fna_filled']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['became_client']);
        $this->assertGreaterThanOrEqual(1, $summary['totals']['became_associate']);
        $this->assertNotEmpty($summary['buckets']);
    }

    public function test_livewire_component_updates_grouping(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Prospects\ProspectActivityLogSummary::class)
            ->assertSet('grouping', 'monthly')
            ->assertSee('Calls Attempted')
            ->set('grouping', 'weekly')
            ->assertSet('grouping', 'weekly')
            ->call('applyPreset', 'today')
            ->assertSet('grouping', 'daily');
    }
}
