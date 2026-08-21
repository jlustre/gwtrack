<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_my_dashboard_and_my_messages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'is_gw_active_associate' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Workspace', false)
            ->assertSee('My Dashboard', false)
            ->assertSee('My Profile', false)
            ->assertSee('My Tasks', false)
            ->assertSee('My Invites', false)
            ->assertSee('My Calendar', false)
            ->assertSee('My Appointments', false)
            ->assertDontSee('My Billing', false)
            ->assertSee('data-sidebar-group="my-workspace"', false)
            ->assertSee('My Messages', false)
            ->assertDontSee('Pre-employment', false);
    }

    public function test_hired_member_sees_dashboard_without_pre_employment_link(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'is_gw_active_associate' => true,
            'recruited_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Workspace', false)
            ->assertSee('My Dashboard', false)
            ->assertSee('My Messages', false)
            ->assertDontSee('Pre-employment', false);
    }

    public function test_messages_link_highlights_on_messages_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('data-server-active-item="my-workspace-my-messages"', false);
    }

    public function test_billing_link_highlights_on_billing_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        app(\App\Services\MonetizationSettingsService::class)->save(true, []);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('data-server-active-item="my-workspace-my-billing"', false);
    }

    public function test_subscription_payments_link_highlights_for_admins_when_billing_is_on(): void
    {
        $this->seed(RolePermissionSeeder::class);

        app(\App\Services\MonetizationSettingsService::class)->save(true, []);

        $user = User::factory()->create();
        $user->assignRole('admin');

        Profile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('Subscription Payments', false)
            ->assertSee('data-server-active-item="admin-management-subscription-payments"', false);
    }

    public function test_sidebar_groups_are_organized_by_relevancy(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'is_gw_active_associate' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-sidebar-group="my-workspace"', false)
            ->assertSee('data-sidebar-group="my-progress"', false)
            ->assertSee('data-sidebar-group="learning-resources"', false)
            ->assertDontSee('data-sidebar-group="performance"', false)
            ->assertSee('data-sidebar-group="prospect-management"', false)
            ->assertSee('data-sidebar-group="my-team"', false)
            ->assertSee('data-sidebar-group="communications"', false)
            ->assertSee('My Progress', false)
            ->assertSee('Learning &amp; Resources', false)
            ->assertSee('Goals &amp; Performance', false)
            ->assertSee('Production Dashboard', false)
            ->assertSee('Rank Advancement', false)
            ->assertSee('FNA Management', false)
            ->assertSee('Summary', false)
            ->assertSee('Follow-Up Center', false)
            ->assertSee('Activity Logs', false)
            ->assertSee('Recruiting Pipeline', false)
            ->assertSee('Sales Pipeline', false)
            ->assertDontSee('Pipeline Board', false)
            ->assertSee('Access Manager', false)
            ->assertSee('Analytics &amp; Goals', false)
            ->assertDontSee('data-sidebar-group="trackers"', false);
    }

    public function test_prospect_management_is_separate_sidebar_group(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'is_gw_active_associate' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-sidebar-group="prospect-management"', false)
            ->assertSee('Summary', false)
            ->assertSee('Follow-Up Center', false)
            ->assertSee('Activity Logs', false)
            ->assertSee('Recruiting Pipeline', false)
            ->assertSee('Sales Pipeline', false)
            ->assertDontSee('Pipeline Board', false)
            ->assertSee('Access Manager', false)
            ->assertSee('Analytics &amp; Goals', false);
    }

    public function test_sales_pipeline_link_highlights_on_pipeline_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'is_gw_active_associate' => true,
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects.pipeline'))
            ->assertOk()
            ->assertSee('data-server-active-item="prospect-management-sales-pipeline"', false);
    }
}
