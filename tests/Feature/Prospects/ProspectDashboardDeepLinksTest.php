<?php

namespace Tests\Feature\Prospects;

use App\Models\User;
use App\Services\DashboardStatsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectDashboardDeepLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_prospect_stat_cards_link_to_expected_routes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $cards = collect(app(DashboardStatsService::class)->personalStatCards($user))
            ->keyBy('key');

        $this->assertSame(route('team.prospects'), $cards->get('prospects')['url']);
        $this->assertSame(
            route('team.prospects', ['prospect_interest' => 'hot']),
            $cards->get('hot_prospects')['url'],
        );
        $this->assertSame(route('team.prospects.follow-ups'), $cards->get('followups_due')['url']);
        $this->assertSame(route('team.prospects'), $cards->get('activities')['url']);
        $this->assertSame(route('team.prospects.analytics'), $cards->get('prospect_conversion')['url']);
    }
}
