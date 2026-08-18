<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardNotificationsPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_owner_dashboard_shows_recent_notifications(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        $owner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();

        for ($i = 1; $i <= 12; $i++) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'database',
                'notifiable_type' => User::class,
                'notifiable_id' => $owner->id,
                'data' => json_encode([
                    'title' => "Agency alert {$i}",
                    'message' => "Notification body {$i}",
                    'category' => 'General',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ]);
        }

        $this->assertSame(12, $owner->fresh()->unreadNotifications()->count());

        $response = $this->actingAs($owner)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Notifications', false)
            ->assertSee('12 unread updates', false)
            ->assertSee('Agency alert 1', false)
            ->assertSee('Agency alert 5', false)
            ->assertSee('Agency alert 8', false)
            ->assertDontSee('Agency alert 9', false);
    }
}
