<?php

namespace Tests\Feature;

use App\Models\AnnouncementReaction;
use App\Models\User;
use App\Services\Communication\AnnouncementAnalyticsService;
use App\Services\Communication\BroadcastService;
use App\Services\Communication\CommunicationHubService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
        ]);

        AnnouncementTestFixtures::seedCategories();
    }

    public function test_admin_communication_dashboard_shows_metrics(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Active announcement',
            'body' => 'Still visible in feed.',
            'audience_type' => 'all',
        ], $admin);
        $hub->publish($announcement);

        $this->actingAs($admin)
            ->get(route('admin.communications.index'))
            ->assertOk()
            ->assertSee('Communication analytics')
            ->assertSee('Active announcement');
    }

    public function test_admin_can_send_broadcast(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Communication\BroadcastCenter::class)
            ->set('title', 'Office closed tomorrow')
            ->set('body', 'The office will be closed due to weather.')
            ->set('priority', 'high')
            ->set('audience_type', 'all')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broadcast_messages', [
            'title' => 'Office closed tomorrow',
            'sender_id' => $admin->id,
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $member->id,
        ]);
    }

    public function test_member_cannot_access_broadcast_center(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.communications.broadcasts'))
            ->assertForbidden();
    }

    public function test_archive_lists_expired_announcements(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Expired policy notice',
            'body' => 'This notice has expired.',
            'audience_type' => 'all',
            'expires_at' => now()->subDay(),
        ], $admin);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementArchive::class)
            ->assertSee('Past announcements')
            ->assertSee('Expired policy notice');
    }

    public function test_analytics_rollup_creates_daily_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Rollup test post',
            'body' => 'Testing analytics rollup.',
            'audience_type' => 'all',
        ], $admin);
        $hub->publish($announcement);
        $hub->markRead($member, $announcement);

        AnnouncementReaction::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
            'reaction' => 'like',
        ]);

        $analytics = app(AnnouncementAnalyticsService::class);
        $count = $analytics->rollupForDate(now());

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('announcement_analytics_daily', [
            'announcement_id' => $announcement->id,
        ]);
    }

    public function test_broadcast_service_preview_audience_count(): void
    {
        User::factory()->count(3)->create()->each(fn (User $user) => $user->assignRole('member'));

        $broadcasts = app(BroadcastService::class);
        $count = $broadcasts->previewAudienceCount('all');

        $this->assertGreaterThanOrEqual(3, $count);
    }
}
