<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\AgencyOwnerFnaReports;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Fna\FnaAnalyticsService;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\DownlineManagementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaAnalyticsScopeTest extends TestCase
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
        ]);
    }

    public function test_agency_report_scopes_to_hierarchy_without_client_pii(): void
    {
        $owner = User::where('email', 'downline-owner@gwtrack.com')->firstOrFail();
        $member = User::where('sponsor_id', $owner->id)->firstOrFail();
        $outsider = User::factory()->create();
        $outsider->assignRole('member');

        app(FnaRecordService::class)->create($member, ['client_name' => 'Secret ClientName']);
        app(FnaRecordService::class)->create($outsider, ['client_name' => 'Outside ClientName']);

        app(DownlineHierarchyService::class)->rebuild();

        $report = app(FnaAnalyticsService::class)->agencyReportFor($owner);

        $this->assertTrue($report['visible']);
        $this->assertGreaterThan(0, $report['member_count']);
        $this->assertNotEmpty($report['by_associate']);

        $memberRow = collect($report['by_associate'])->firstWhere('user_id', $member->id);
        $this->assertNotNull($memberRow);
        $this->assertSame(1, $memberRow['created']);

        $outsiderRow = collect($report['by_associate'])->firstWhere('user_id', $outsider->id);
        $this->assertNull($outsiderRow);

        $this->actingAs($owner)
            ->get(route('team.fna.reports.agency'))
            ->assertOk()
            ->assertSee('By Associate')
            ->assertSee('team members in scope')
            ->assertDontSee('Secret ClientName')
            ->assertDontSee('Outside ClientName');

        Livewire::actingAs($owner)
            ->test(AgencyOwnerFnaReports::class)
            ->assertSee('team members in scope')
            ->assertSee($member->name);
    }

    public function test_associate_without_downline_does_not_see_agency_report(): void
    {
        $associate = User::factory()->create();
        $associate->assignRole('member');

        $report = app(FnaAnalyticsService::class)->agencyReportFor($associate);

        $this->assertFalse($report['visible']);

        Livewire::actingAs($associate)
            ->test(AgencyOwnerFnaReports::class)
            ->assertForbidden();
    }
}
