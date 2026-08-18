<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\MemberProductionEntry;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmTraineeSuccessAnalyticsService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class CfmTraineeSuccessAnalyticsTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);
    }

    public function test_calculates_time_to_license_and_first_sale(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create([
            'mentor_id' => $cfm->id,
            'joined_at' => now()->subDays(120),
        ]);

        MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'assigned_by' => $cfm->id,
            'status' => 'active',
            'started_at' => now()->subDays(100)->toDateString(),
            'confirmed_at' => now()->subDays(100),
        ]);

        $this->startChecklistType($trainee, 'licensing');

        $licensingIds = Checklist::query()->forTypeCode('licensing')->active()->pluck('id');

        foreach ($licensingIds as $checklistId) {
            ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $trainee->id,
                    'checklist_id' => $checklistId,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => 'completed',
                    'completed_at' => now()->subDays(40),
                ],
            );
        }

        MemberProductionEntry::query()->create([
            'user_id' => $trainee->id,
            'source' => 'manual',
            'description' => 'First policy',
            'annual_premium' => 1200,
            'status' => 'posted',
            'posted_at' => now()->subDays(25)->toDateString(),
        ]);

        $summary = app(CfmTraineeSuccessAnalyticsService::class)->summaryFor($cfm);

        $this->assertSame(60.0, $summary['avg_time_to_license_days']);
        $this->assertSame(75.0, $summary['avg_time_to_first_sale_days']);
        $this->assertSame(1, $summary['sample_sizes']['licensed']);
        $this->assertSame(1, $summary['sample_sizes']['first_sale']);
        $this->assertArrayHasKey('time_to_license', $summary['cfm_vs_agency']);
    }

    public function test_dashboard_shows_success_analytics_section(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $this->actingAs($cfm)
            ->get(route('cfm.effectiveness.index'))
            ->assertOk()
            ->assertSee('Trainee Success Analytics')
            ->assertSee('Time to License')
            ->assertSee('Time to First Sale');
    }
}
