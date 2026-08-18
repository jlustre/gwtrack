<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Communication\CommunicationHubService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseTwoTest extends TestCase
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

    public function test_search_filters_communication_hub_feed(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);

        $matching = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Spring production challenge kickoff',
            'body' => 'Join the agency-wide production challenge.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($matching);

        $other = $hub->createDraft([
            'category_code' => 'training',
            'title' => 'Licensing webinar reminder',
            'body' => 'Do not miss Thursday licensing prep.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($other);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CommunicationHub::class)
            ->set('search', 'production challenge')
            ->assertSee('Spring production challenge kickoff')
            ->assertDontSee('Licensing webinar reminder');
    }

    public function test_user_can_bookmark_and_view_saved_announcements(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Bookmark this policy update',
            'body' => 'Important policy details for all associates.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementShow::class, ['announcement' => $announcement])
            ->call('toggleBookmark')
            ->assertSet('isBookmarked', true);

        $this->assertDatabaseHas('announcement_bookmarks', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
        ]);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementBookmarks::class)
            ->assertSee('Bookmark this policy update')
            ->call('removeBookmark', $announcement->id);

        $this->assertDatabaseMissing('announcement_bookmarks', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_viewing_announcement_marks_full_read_with_tracking_fields(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Read tracking announcement',
            'body' => 'Track first view and full open state.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        $this->actingAs($member)
            ->get(route('communications.show', $announcement))
            ->assertOk();

        $this->assertDatabaseHas('message_center_announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
            'opened_full' => true,
        ]);
    }

    public function test_unread_count_and_featured_appear_on_dashboard_snapshot(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);

        $featured = $hub->createDraft([
            'category_code' => 'leadership',
            'title' => 'Featured leadership update',
            'body' => 'This week we focus on team culture.',
            'audience_type' => 'all',
            'is_featured' => true,
        ], $author);
        $hub->publish($featured);

        $unread = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Unread general update',
            'body' => 'General update body.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($unread);

        $snapshot = $hub->dashboardCommunicationsFor($member);

        $this->assertSame(2, $snapshot['unread_count']);
        $this->assertCount(1, $snapshot['featured']);
        $this->assertSame('Featured leadership update', $snapshot['featured'][0]['title']);
        $this->assertTrue($snapshot['announcements'][0]['is_unread']);
    }

    public function test_marking_announcement_notification_read_clears_unread_state(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Notification read sync test',
            'body' => 'This should clear unread when the notification is marked read.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        $notification = $member->notifications()->firstOrFail();

        app(\App\Services\Notifications\NotificationInboxService::class)
            ->markAsRead($member, $notification->id);

        $this->assertDatabaseHas('message_center_announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
        ]);

        $snapshot = $hub->dashboardCommunicationsFor($member->fresh());

        $this->assertSame(0, $snapshot['unread_count']);
        $this->assertFalse($snapshot['announcements'][0]['is_unread']);
    }

    public function test_featured_and_pinned_sections_render_on_hub_home(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);

        $announcement = $hub->createDraft([
            'category_code' => 'leadership',
            'title' => 'Pinned and featured leadership note',
            'body' => 'Leadership priorities for the week.',
            'audience_type' => 'all',
            'is_pinned' => true,
            'is_featured' => true,
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CommunicationHub::class)
            ->assertSee('Featured')
            ->assertSee('Pinned updates')
            ->assertSee('Pinned and featured leadership note');
    }
}
