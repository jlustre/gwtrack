<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardStatDetailsUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_embeds_relative_stat_details_template_and_endpoint_responds(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
            TeamSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $user = User::factory()->create([
            'name' => 'Dashboard Viewer',
            'email' => 'dash.viewer@example.com',
            'password' => Hash::make('Password123'),
            'team_id' => $teamId,
            'rank_id' => $rankId,
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $user->syncRoles(['member']);
        $user->profile()->create(array_merge(
            LocationOptions::profileLocationIds('Canada'),
            ['city' => 'Toronto', 'gw_associate_id' => 'GW-DASH-VIEW']
        ));

        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $content = $dashboard->getContent();
        $this->assertStringContainsString('detailsUrlTemplate', $content);
        $this->assertStringContainsString('__TYPE__', $content);
        $this->assertStringContainsString('dashboardStats', $content);

        $this->actingAs($user)
            ->getJson('/dashboard/stats/profile/members?context=team')
            ->assertOk()
            ->assertJsonPath('display', 'progress')
            ->assertJsonPath('type', 'profile');
    }
}
