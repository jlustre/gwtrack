<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\MemberProductionEntry;
use App\Models\User;
use App\Services\Communication\AnnouncementEventService;
use App\Services\Communication\CampaignService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseFiveTest extends TestCase
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

    public function test_campaign_center_lists_active_campaigns(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $campaigns = app(CampaignService::class);
        $campaign = $campaigns->createCampaign([
            'name' => 'March Production Challenge',
            'type' => 'production',
            'description' => 'Top producers win prizes this month.',
            'rules' => 'Count posted AP during March.',
            'prizes' => ['1st: $500', '2nd: $250'],
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ], $admin);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CampaignCenter::class)
            ->assertSee('Campaign Center')
            ->assertSee('March Production Challenge');
    }

    public function test_user_can_join_campaign_and_appear_on_leaderboard(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        MemberProductionEntry::query()->create([
            'user_id' => $member->id,
            'source' => 'manual',
            'description' => 'Test policy',
            'annual_premium' => 5000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $campaigns = app(CampaignService::class);
        $campaign = $campaigns->createCampaign([
            'name' => 'Production Sprint',
            'type' => 'production',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeek(),
        ], $admin);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CampaignShow::class, ['campaign' => $campaign])
            ->call('join')
            ->assertSee('Production Sprint')
            ->assertSee($member->name);

        $this->assertDatabaseHas('announcement_campaign_participants', [
            'campaign_id' => $campaign->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_event_announcement_creates_calendar_event_and_supports_rsvp(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');
        $member->givePermissionTo('view calendar');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $events = app(AnnouncementEventService::class);
        $announcement = $events->publishEventAnnouncement([
            'title' => 'Agency-wide webinar',
            'summary' => 'Join us for product training.',
            'body' => 'We will cover new product updates and field best practices.',
            'starts_at' => now()->addDays(3)->toDateTimeString(),
            'location' => 'Zoom',
            'meeting_link' => 'https://zoom.us/j/example',
        ], $admin);

        $this->assertNotNull($announcement->calendar_event_id);
        $this->assertDatabaseHas('calendar_events', ['title' => 'Agency-wide webinar']);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementEventRsvp::class, ['announcement' => $announcement])
            ->call('accept')
            ->assertSet('rsvpStatus', 'accepted');

        $this->assertDatabaseHas('calendar_event_attendees', [
            'calendar_event_id' => $announcement->calendar_event_id,
            'user_id' => $member->id,
            'rsvp_status' => 'accepted',
        ]);
    }

    public function test_admin_can_create_campaign_via_composer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Communication\CampaignComposer::class)
            ->set('name', 'Spring Recruiting Rally')
            ->set('type', 'recruiting')
            ->set('description', 'Recruit new associates this spring.')
            ->set('rules', 'Most recruits wins.')
            ->set('prizes', "1st place: Recognition dinner\n2nd place: Gift card")
            ->set('publish_announcement', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('announcement_campaigns', [
            'name' => 'Spring Recruiting Rally',
            'type' => 'recruiting',
        ]);

        $this->assertDatabaseHas('message_center_announcements', [
            'title' => 'Spring Recruiting Rally is now live',
            'status' => 'published',
        ]);
    }

    public function test_announcement_show_links_to_campaign_when_present(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $campaigns = app(CampaignService::class);
        $campaign = $campaigns->createCampaign([
            'name' => 'Licensing Push',
            'type' => 'licensing',
            'starts_at' => now()->subDay(),
        ], $admin);

        $hub = app(\App\Services\Communication\CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'campaign',
            'title' => 'Join the licensing push',
            'body' => 'Complete licensing milestones this month.',
            'campaign_id' => $campaign->id,
        ], $admin);
        $hub->publish($announcement);

        $this->actingAs($member)
            ->get(route('communications.show', $announcement))
            ->assertOk()
            ->assertSee('Licensing Push')
            ->assertSee('View campaign leaderboard');
    }
}
