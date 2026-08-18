<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\ProfileCompletionService;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stat_cards_show_zero_tracker_progress_for_new_user(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $profileCompletion = app(ProfileCompletionService::class)->snapshot($user);
        $statCards = app(DashboardStatsService::class)->statCards($user, $profileCompletion);

        $this->assertSame('0%', $statCards[1]['value']);
        $this->assertSame('0%', $statCards[2]['value']);
        $this->assertSame('0%', $statCards[3]['value']);
        $this->assertSame('0%', $statCards[4]['value']);

        $this->assertSame('My Profile', $statCards[0]['label']);
        $this->assertSame('My Onboarding', $statCards[1]['label']);
        $this->assertSame('My Credentials', $statCards[2]['label']);
        $this->assertSame('My Apprenticeship', $statCards[3]['label']);
        $this->assertSame('My Trainings', $statCards[4]['label']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('statCards', $statCards)
            ->assertSee('My Profile', false)
            ->assertSee('My Onboarding', false)
            ->assertSee('My Credentials', false)
            ->assertSee('My Apprenticeship', false)
            ->assertSee('My Trainings', false)
            ->assertDontSee('65%', false)
            ->assertDontSee('40%', false)
            ->assertDontSee('25%', false)
            ->assertDontSee('70%', false);
    }

    public function test_dashboard_stat_cards_reflect_real_tracker_progress(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->profile()->create(array_merge([
            'is_gw_active_associate' => true,
            'gw_associate_id' => 'GW-DASH-1',
        ], LocationOptions::profileLocationIds('Canada')));

        $onboardingStepId = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'onboarding')
            ->where('checklists.title', 'Complete Member Profile')
            ->value('checklists.id');

        DB::table('checklist_progress')->insert([
            'user_id' => $user->id,
            'checklist_id' => $onboardingStepId,
            'mentor_assignment_id' => null,
            'status' => 'completed',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $licensingStepId = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'licensing')
            ->where('checklists.title', 'Confirm Licensing Jurisdiction')
            ->value('checklists.id');

        DB::table('checklist_progress')->insert([
            'user_id' => $user->id,
            'checklist_id' => $licensingStepId,
            'mentor_assignment_id' => null,
            'status' => 'completed',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $apprenticeshipStepId = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'fap')
            ->where('checklists.title', 'FAP Orientation With Sponsor And CFM')
            ->value('checklists.id');

        DB::table('checklist_progress')->insert([
            'user_id' => $user->id,
            'checklist_id' => $apprenticeshipStepId,
            'mentor_assignment_id' => null,
            'status' => 'completed',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dashboardStats = app(DashboardStatsService::class);
        $profileCompletion = app(ProfileCompletionService::class)->snapshot($user);
        $expected = $dashboardStats->statCards($user, $profileCompletion);

        $this->assertGreaterThan(0, $expected[0]['bar']);
        $this->assertGreaterThan(0, $expected[1]['bar']);
        $this->assertGreaterThan(0, $expected[2]['bar']);
        $this->assertGreaterThan(0, $expected[3]['bar']);
        $this->assertSame(0, $expected[4]['bar']);

        $this->assertSame('My Profile', $expected[0]['label']);
        $this->assertSame('My Onboarding', $expected[1]['label']);
        $this->assertSame('My Credentials', $expected[2]['label']);
        $this->assertSame('My Apprenticeship', $expected[3]['label']);
        $this->assertSame('My Trainings', $expected[4]['label']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('statCards', $expected)
            ->assertSee('My Profile', false)
            ->assertSee('My Credentials', false)
            ->assertSee('My Trainings', false)
            ->assertSee($expected[0]['value'], false)
            ->assertSee($expected[1]['value'], false)
            ->assertSee($expected[2]['value'], false)
            ->assertSee($expected[3]['value'], false)
            ->assertSee('0%', false);
    }

    public function test_dashboard_handles_user_without_profile(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProfileCompletionFieldSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $profileCompletion = app(ProfileCompletionService::class)->snapshot($user);
        $statCards = app(DashboardStatsService::class)->statCards($user, $profileCompletion);

        $this->assertIsArray($statCards);
        $this->assertCount(5, $statCards);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('statCards');
    }
}
