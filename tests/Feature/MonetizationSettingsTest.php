<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminSettingsPage;
use App\Models\User;
use App\Services\MonetizationSettingsService;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonetizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            RankSeeder::class,
        ]);
    }

    public function test_admin_can_open_settings_and_save_monetization(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Rank-based monthly billing')
            ->assertSee('Senior Associate (SA)')
            ->assertSee('AVP & SVP');

        Livewire::actingAs($admin)
            ->test(AdminSettingsPage::class)
            ->set('monetizationEnabled', true)
            ->set('prices.sa', '10.50')
            ->set('prices.avp_svp', '24.95')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Monetization settings saved.');

        $settings = app(MonetizationSettingsService::class);

        $this->assertTrue($settings->enabled());
        $this->assertTrue(session(MonetizationSettingsService::SESSION_KEY));
        $this->assertSame(10.50, $settings->amountForRank('SA'));
        $this->assertSame(24.95, $settings->amountForRank('AVP'));
        $this->assertSame(24.95, $settings->amountForRank('SVP'));
        $this->assertSame(0.0, $settings->amountForRank('TA'));
    }

    public function test_member_cannot_open_admin_settings(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.settings'))
            ->assertForbidden();
    }

    public function test_agency_owner_can_view_but_not_save_monetization_settings(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $this->actingAs($owner)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Only super admins and admins can change monetization settings.');

        Livewire::actingAs($owner)
            ->test(AdminSettingsPage::class)
            ->set('monetizationEnabled', true)
            ->call('save')
            ->assertForbidden();

        $this->assertFalse(app(MonetizationSettingsService::class)->enabled());
    }
}
