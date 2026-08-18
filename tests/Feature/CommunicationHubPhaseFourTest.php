<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\Communication\LeadershipDeskService;
use App\Services\Communication\RecognitionService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RecognitionBadgeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
            RecognitionBadgeSeeder::class,
        ]);

        AnnouncementTestFixtures::seedCategories();
    }

    public function test_recognition_center_displays_recognition_posts(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $honoree = User::factory()->create(['name' => 'Jordan Smith']);
        $honoree->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $recognition = app(RecognitionService::class);
        $badgeId = Badge::query()->where('slug', 'new-license')->value('id');
        $rendered = $recognition->renderTemplate('new_license', $honoree);
        $post = $recognition->createRecognitionPost([
            'recognition_type' => 'new_license',
            'honoree_user_id' => $honoree->id,
            'title' => $rendered['title'],
            'summary' => $rendered['summary'],
            'body' => $rendered['body'],
            'badge_id' => $badgeId,
            'is_featured' => true,
        ], $author);
        $recognition->publishRecognition($post, $author);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\RecognitionCenter::class)
            ->assertSee('Recognition Center')
            ->assertSee('Jordan Smith')
            ->assertSee('New License');
    }

    public function test_publish_recognition_awards_badge_to_honoree(): void
    {
        $honoree = User::factory()->create();
        $honoree->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('team-leader');

        $recognition = app(RecognitionService::class);
        $badgeId = Badge::query()->where('slug', 'top-producer')->value('id');
        $rendered = $recognition->renderTemplate('top_producer', $honoree);

        $post = $recognition->createRecognitionPost([
            'recognition_type' => 'top_producer',
            'honoree_user_id' => $honoree->id,
            'title' => $rendered['title'],
            'summary' => $rendered['summary'],
            'body' => $rendered['body'],
            'badge_id' => $badgeId,
        ], $author);

        $recognition->publishRecognition($post, $author);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $honoree->id,
            'badge_id' => $badgeId,
            'announcement_id' => $post->id,
        ]);
    }

    public function test_leadership_desk_shows_leadership_messages(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(\App\Services\Communication\CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'leadership',
            'title' => 'Vision update for Q3',
            'summary' => 'Our agency focus for the next quarter.',
            'body' => 'Leadership is aligning around growth, licensing velocity, and team culture.',
            'priority' => 'important',
            'audience_type' => 'all',
            'is_featured' => true,
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\LeadershipDesk::class)
            ->assertSee('Leadership Desk')
            ->assertSee('Vision update for Q3')
            ->assertSee('Featured leadership message');
    }

    public function test_team_leader_can_compose_recognition_via_template(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $honoree = User::factory()->create(['name' => 'Taylor Lee']);
        $honoree->assignRole('member');

        Livewire::actingAs($leader)
            ->test(\App\Livewire\Communication\RecognitionComposer::class)
            ->set('template', 'promotion')
            ->set('honoree_user_id', $honoree->id)
            ->set('publish_now', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('message_center_announcements', [
            'title' => 'Taylor Lee has been promoted!',
            'status' => 'published',
        ]);

        $this->assertGreaterThan(0, UserBadge::query()->where('user_id', $honoree->id)->count());
    }

    public function test_legacy_recognition_route_redirects_to_communications_recognition(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get('/recognition')
            ->assertRedirect('/communications/recognition');
    }

    public function test_leadership_desk_service_returns_dashboard_items(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(\App\Services\Communication\CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'leadership',
            'title' => 'Weekly leadership note',
            'body' => 'Stay focused on field activity.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        $items = app(LeadershipDeskService::class)->latestForDashboard($member);

        $this->assertCount(1, $items);
        $this->assertSame('Weekly leadership note', $items[0]['title']);
    }
}
