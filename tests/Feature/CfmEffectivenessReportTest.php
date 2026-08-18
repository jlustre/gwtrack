<?php

namespace Tests\Feature;

use App\Models\CfmEffectiveness\CfmEffectivenessReport;
use App\Models\User;
use Database\Seeders\CfmEffectivenessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmEffectivenessReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CfmEffectivenessSeeder::class);
    }

    public function test_cfm_can_view_reports_page(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $this->actingAs($cfm)
            ->get(route('cfm.effectiveness.reports'))
            ->assertOk()
            ->assertSee('Effectiveness Reports')
            ->assertSee('Generate & Download PDF');
    }

    public function test_cfm_can_generate_and_download_effectiveness_report(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        Livewire::actingAs($cfm)
            ->test(\App\Livewire\CfmEffectiveness\CfmEffectivenessReports::class)
            ->set('reportType', 'effectiveness_summary')
            ->set('periodType', 'quarterly')
            ->call('generateReport')
            ->assertRedirect();

        $report = CfmEffectivenessReport::query()->where('cfm_id', $cfm->id)->firstOrFail();
        $this->assertSame('effectiveness_summary', $report->report_type);
        $this->assertIsArray($report->payload);
        $this->assertArrayHasKey('effectiveness_score', $report->payload);

        $response = $this->actingAs($cfm)
            ->get(route('cfm.effectiveness.reports.download', $report));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_agency_owner_can_generate_mentor_comparison_report(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        Livewire::actingAs($owner)
            ->test(\App\Livewire\CfmEffectiveness\CfmEffectivenessReports::class)
            ->set('cfmId', $cfm->id)
            ->set('reportType', 'mentor_comparison')
            ->set('periodType', 'quarterly')
            ->call('generateReport')
            ->assertRedirect();

        $report = CfmEffectivenessReport::query()->where('cfm_id', $cfm->id)->firstOrFail();
        $this->assertSame('mentor_comparison', $report->report_type);
        $this->assertArrayHasKey('leaderboard', $report->payload);
        $this->assertArrayHasKey('agency_overview', $report->payload);
    }

    public function test_cfm_cannot_download_another_cfms_report(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $otherCfm = User::factory()->create();
        $otherCfm->assignRole('certified-field-mentor');

        $report = CfmEffectivenessReport::query()->create([
            'cfm_id' => $otherCfm->id,
            'report_type' => 'effectiveness_summary',
            'audience' => 'cfm',
            'period_type' => 'quarterly',
            'period_start' => now()->subMonths(3)->toDateString(),
            'period_end' => now()->toDateString(),
            'payload' => ['effectiveness_score' => 80],
            'export_format' => 'pdf',
            'generated_by' => $otherCfm->id,
        ]);

        $this->actingAs($cfm)
            ->get(route('cfm.effectiveness.reports.download', $report))
            ->assertForbidden();
    }
}
