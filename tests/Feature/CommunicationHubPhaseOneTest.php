<?php

namespace Tests\Feature;

use App\Models\AnnouncementCategory;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use App\Services\Communication\CommunicationHubService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseOneTest extends TestCase
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

    public function test_member_can_view_communication_hub_feed(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Portal maintenance tonight',
            'body' => 'Scheduled maintenance from 11 PM to 1 AM.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        $this->actingAs($member)
            ->get(route('communications.index'))
            ->assertOk()
            ->assertSee('Communication Hub')
            ->assertSee('Portal maintenance tonight');
    }

    public function test_publish_dispatches_announcement_published_notification(): void
    {
        $author = User::factory()->create();
        $author->assignRole('admin');

        $recipient = User::factory()->create();
        $recipient->assignRole('member');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'training',
            'title' => 'New webinar scheduled',
            'body' => 'Join us Thursday at 7 PM.',
            'audience_type' => 'all',
        ], $author);

        $hub->publish($announcement);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
        ]);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'trigger_code' => 'announcement_published',
            'channel' => 'in_app',
            'status' => 'sent',
            'user_id' => $recipient->id,
        ]);
    }

    public function test_admin_can_compose_and_publish_announcement_via_livewire(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $categoryId = AnnouncementCategory::query()->where('code', 'general')->value('id');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Communication\AnnouncementComposer::class)
            ->set('category_id', $categoryId)
            ->set('title', 'Spring kickoff meeting')
            ->set('summary', 'Join the agency-wide kickoff call.')
            ->set('body', 'We will review Q2 goals and recognition winners.')
            ->set('priority', 'important')
            ->set('audience_type', 'all')
            ->set('publish_now', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('message_center_announcements', [
            'title' => 'Spring kickoff meeting',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);
    }

    public function test_user_can_acknowledge_required_announcement(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'compliance',
            'title' => 'Policy update requires acknowledgement',
            'body' => 'Updated compliance policy is effective immediately.',
            'audience_type' => 'all',
            'requires_acknowledgement' => true,
            'priority' => 'critical',
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementShow::class, ['announcement' => $announcement])
            ->assertSee('requires your acknowledgement')
            ->call('acknowledge')
            ->assertSee('You acknowledged this announcement');

        $this->assertDatabaseHas('announcement_acknowledgements', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_legacy_announcements_route_redirects_to_communications_hub(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get('/announcements')
            ->assertRedirect('/communications');
    }

    public function test_announcement_categories_can_be_seeded_for_tests(): void
    {
        $this->assertDatabaseCount('announcement_categories', 11);
        $this->assertDatabaseHas('announcement_categories', [
            'code' => 'leadership',
            'name' => 'Leadership Message',
        ]);
    }
}
