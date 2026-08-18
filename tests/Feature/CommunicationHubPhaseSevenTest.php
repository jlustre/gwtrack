<?php

namespace Tests\Feature;

use App\Jobs\Communication\SendNewsletterJob;
use App\Models\User;
use App\Services\Communication\CommunicationAiAssistantService;
use App\Services\Communication\CommunicationHubService;
use App\Services\Communication\NewsletterGeneratorService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\AnnouncementTestFixtures;
use Tests\TestCase;

class CommunicationHubPhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
        ]);

        AnnouncementTestFixtures::seedTemplates();
    }

    public function test_admin_can_access_newsletter_generator(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.communications.newsletters'))
            ->assertOk()
            ->assertSee('Newsletter generator');
    }

    public function test_member_cannot_access_newsletter_generator(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.communications.newsletters'))
            ->assertForbidden();
    }

    public function test_compile_weekly_newsletter_includes_published_announcements(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Weekly hub highlight',
            'summary' => 'Important update for the team.',
            'body' => 'Full details inside the hub.',
            'audience_type' => 'all',
        ], $admin);
        $hub->publish($announcement);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Communication\NewsletterGenerator::class)
            ->set('period_type', 'weekly')
            ->call('generate')
            ->assertHasNoErrors()
            ->assertSet('previewNewsletterId', fn ($id) => $id !== null);

        $this->assertDatabaseHas('announcement_newsletters', [
            'period_type' => 'weekly',
            'status' => 'ready',
            'created_by' => $admin->id,
        ]);
    }

    public function test_ai_assistant_generates_template_based_draft(): void
    {
        config([
            'communication-hub.ai.enabled' => true,
            'communication-hub.ai.use_llm' => false,
        ]);

        $ai = app(CommunicationAiAssistantService::class);
        $draft = $ai->generateDraft('announcement', [
            'topic' => 'licensing deadline',
            'author_name' => 'Agency Owner',
            'template_code' => 'general-update',
        ]);

        $this->assertSame('template', $draft['source']);
        $this->assertStringContainsString('licensing deadline', $draft['title']);
        $this->assertNotEmpty($draft['body']);
    }

    public function test_composer_can_suggest_ai_draft(): void
    {
        config([
            'communication-hub.ai.enabled' => true,
            'communication-hub.ai.use_llm' => false,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = \App\Models\AnnouncementCategory::query()->where('code', 'general')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Communication\AnnouncementComposer::class)
            ->set('category_id', $category->id)
            ->set('ai_topic', 'New training module launch')
            ->call('suggestDraft')
            ->assertHasNoErrors()
            ->assertSet('title', fn ($title) => str_contains($title, 'New training module launch'));
    }

    public function test_send_newsletter_job_marks_newsletter_sent(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        $member = User::factory()->create(['email' => 'member@example.com']);
        $member->assignRole('member');

        $hub = app(CommunicationHubService::class);
        $announcement = $hub->createDraft([
            'category_code' => 'general',
            'title' => 'Newsletter item',
            'body' => 'Body content.',
            'audience_type' => 'all',
        ], $admin);
        $hub->publish($announcement);

        $newsletter = app(NewsletterGeneratorService::class)->compile($admin, 'weekly');

        (new SendNewsletterJob($newsletter->id, $admin->id, 'all'))->handle(
            app(NewsletterGeneratorService::class),
            app(\App\Services\Communication\AnnouncementAudienceResolver::class),
        );

        $newsletter->refresh();
        $this->assertSame('sent', $newsletter->status);
        $this->assertGreaterThan(0, $newsletter->sent_count);
        Mail::assertQueued(\App\Mail\CommunicationNewsletterMail::class);
    }
}
