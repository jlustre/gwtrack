<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Communication\AnnouncementAcknowledgementService;
use App\Services\Communication\CommunicationHubService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseThreeTest extends TestCase
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

    public function test_user_can_react_to_announcement(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'React to this update',
            'body' => 'Try the new reaction buttons.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementReactions::class, ['announcement' => $announcement])
            ->call('react', 'like')
            ->assertSet('userReaction', 'like');

        $this->assertDatabaseHas('announcement_reactions', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
            'reaction' => 'like',
        ]);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementReactions::class, ['announcement' => $announcement])
            ->call('react', 'like')
            ->assertSet('userReaction', null);

        $this->assertDatabaseMissing('announcement_reactions', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_user_can_post_comment_and_reply(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Discuss this update',
            'body' => 'Leave a comment below.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementComments::class, ['announcement' => $announcement])
            ->set('body', 'Great update — thanks for sharing.')
            ->call('postComment')
            ->assertHasNoErrors()
            ->assertSee('Great update — thanks for sharing.');

        $commentId = \App\Models\AnnouncementComment::query()->value('id');

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementComments::class, ['announcement' => $announcement])
            ->call('startReply', $commentId)
            ->set('body', 'Adding a follow-up reply.')
            ->call('postComment')
            ->assertSee('Adding a follow-up reply.');

        $this->assertDatabaseCount('announcement_comments', 2);
    }

    public function test_critical_banner_shows_until_acknowledgement(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'emergency',
            'title' => 'Emergency compliance notice',
            'summary' => 'Immediate acknowledgement required.',
            'body' => 'Read and acknowledge this emergency notice.',
            'priority' => 'emergency',
            'audience_type' => 'all',
            'requires_acknowledgement' => true,
        ], $author);
        $hub->publish($announcement);

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CommunicationCriticalBanner::class)
            ->assertSee('Emergency compliance notice')
            ->assertSee('Acknowledgement required');

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\AnnouncementShow::class, ['announcement' => $announcement])
            ->call('acknowledge');

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CommunicationCriticalBanner::class)
            ->assertDontSee('Emergency compliance notice');
    }

    public function test_admin_acknowledgement_report_shows_pending_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create(['name' => 'Pending Member']);
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'compliance',
            'title' => 'Policy acknowledgement report test',
            'body' => 'Everyone must acknowledge.',
            'priority' => 'critical',
            'audience_type' => 'all',
            'requires_acknowledgement' => true,
        ], $author);
        $hub->publish($announcement);

        $report = app(AnnouncementAcknowledgementService::class)->acknowledgementReport();
        $row = collect($report)->firstWhere('title', 'Policy acknowledgement report test');
        $this->assertNotNull($row);
        $this->assertSame($row['audience_total'], $row['pending_count']);
        $this->assertGreaterThan(0, $row['pending_count']);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Communication\AdminAnnouncementAcknowledgements::class)
            ->assertSee('Policy acknowledgement report test')
            ->call('showDetail', $announcement->id)
            ->assertSee('Pending Member');
    }

    public function test_feed_shows_engagement_counts(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $author = User::factory()->create();
        $author->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Engagement metrics post',
            'body' => 'This post has reactions and comments.',
            'audience_type' => 'all',
        ], $author);
        $hub->publish($announcement);

        $engagement = app(\App\Services\Communication\AnnouncementEngagementService::class);
        $engagement->toggleReaction($member, $announcement, 'celebrate');
        $engagement->addComment($member, $announcement, 'Looks good to me.');

        Livewire::actingAs($member)
            ->test(\App\Livewire\Communication\CommunicationHub::class)
            ->assertSee('Engagement metrics post')
            ->assertSee('1 reaction')
            ->assertSee('1 comment');
    }
}
