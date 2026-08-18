<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationDeviceToken;
use App\Models\Profile;
use App\Models\User;
use App\Services\Notifications\NotificationInsightService;
use App\Services\Notifications\NotificationOrchestrator;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            NotificationConfigSeeder::class,
        ]);
    }

    public function test_sms_channel_logs_delivery_when_enabled(): void
    {
        Config::set('notifications.sms.enabled', true);

        Log::spy();

        $member = User::factory()->create();
        Profile::query()->updateOrCreate(
            ['user_id' => $member->id],
            ['phone' => '+15551234567'],
        );

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'mentor_assigned',
            'queue' => false,
            'channels' => ['in_app', 'sms'],
            'recipients' => [$member->id],
            'template_data' => [
                'member_name' => $member->name,
                'mentor_name' => 'Celeste Navarro',
            ],
            'sms_body' => 'Your CFM Celeste Navarro is ready to connect.',
        ]);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $member->id,
            'trigger_code' => 'mentor_assigned',
            'channel' => 'sms',
            'status' => 'sent',
        ]);

        Log::shouldHaveReceived('info')->atLeast()->once();
    }

    public function test_push_channel_skips_when_no_device_tokens(): void
    {
        Config::set('notifications.push.enabled', true);

        $member = User::factory()->create();

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'training_assigned',
            'queue' => false,
            'channels' => ['in_app', 'push'],
            'recipients' => [$member->id],
            'template_data' => [
                'member_name' => $member->name,
                'module_title' => 'Foundations',
            ],
            'push_title' => 'Training assigned',
            'push_body' => 'A new course is waiting for you.',
        ]);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $member->id,
            'channel' => 'push',
            'status' => 'skipped',
        ]);
    }

    public function test_push_channel_delivers_when_device_token_exists(): void
    {
        Config::set('notifications.push.enabled', true);

        Log::spy();

        $member = User::factory()->create();

        NotificationDeviceToken::query()->create([
            'user_id' => $member->id,
            'token' => 'web-token-abc123',
            'platform' => 'web',
            'device_name' => 'Chrome',
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'training_assigned',
            'queue' => false,
            'channels' => ['push'],
            'recipients' => [$member->id],
            'push_title' => 'Training assigned',
            'push_body' => 'A new course is waiting for you.',
            'title' => 'Training assigned',
            'message' => 'A new course is waiting for you.',
        ]);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $member->id,
            'channel' => 'push',
            'status' => 'sent',
        ]);
    }

    public function test_user_can_register_and_revoke_device_token(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $subscription = json_encode([
            'endpoint' => 'https://push.example.com/device/abc123',
            'keys' => ['p256dh' => 'test', 'auth' => 'test'],
        ]);

        $this->actingAs($member)
            ->postJson(route('notifications.device-tokens.store'), [
                'token' => 'abc123',
                'platform' => 'web',
                'device_name' => 'Safari',
                'subscription_payload' => $subscription,
            ])
            ->assertOk()
            ->assertJsonPath('platform', 'web');

        $storedToken = hash('sha256', 'https://push.example.com/device/abc123');

        $this->assertDatabaseHas('notification_device_tokens', [
            'user_id' => $member->id,
            'token' => $storedToken,
            'is_active' => true,
        ]);

        $this->actingAs($member)
            ->deleteJson(route('notifications.device-tokens.destroy'), [
                'token' => $storedToken,
            ])
            ->assertOk();

        $this->assertDatabaseHas('notification_device_tokens', [
            'user_id' => $member->id,
            'token' => $storedToken,
            'is_active' => false,
        ]);
    }

    public function test_notification_preferences_include_push_and_sms_channels(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('notifications.preferences'))
            ->assertOk()
            ->assertSee('Mobile & browser delivery', false)
            ->assertSee('Enable push on this device', false)
            ->assertSee('SMS alerts', false)
            ->assertSee('Push', false);
    }

    public function test_vapid_public_key_endpoint_returns_configuration(): void
    {
        Config::set('notifications.push.enabled', true);
        Config::set('notifications.push.vapid.public_key', 'test-public-key');

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->getJson(route('notifications.push.vapid-public-key'))
            ->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('public_key', 'test-public-key');
    }

    public function test_training_assigned_template_includes_push_channel(): void
    {
        $channels = json_decode(
            (string) \Illuminate\Support\Facades\DB::table('notification_templates')
                ->join('notification_triggers', 'notification_triggers.id', '=', 'notification_templates.notification_trigger_id')
                ->where('notification_triggers.code', 'training_assigned')
                ->where('notification_templates.is_default', true)
                ->value('notification_templates.channels'),
            true,
        );

        $this->assertContains('push', $channels);
    }

    public function test_ai_insights_enrich_notification_when_enabled(): void
    {
        Config::set('notifications.ai.insights_enabled', true);

        $member = User::factory()->create();

        $notifications = app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'task_assigned',
            'queue' => false,
            'recipients' => [$member->id],
            'title' => 'Task assigned',
            'message' => 'Review onboarding checklist.',
        ]);

        $notification = Notification::query()->find($notifications->first()->id);

        $this->assertNotNull(data_get($notification->metadata, 'ai_summary'));
        $this->assertNotEmpty(data_get($notification->metadata, 'suggested_actions'));

        $count = app(NotificationInsightService::class)->generateDailySummaries();
        $this->assertSame(0, $count);
    }

    public function test_email_respects_user_preference_suppression(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);
        $member->assignRole('member');

        $trainingTypeId = \App\Models\NotificationType::query()->where('code', 'training')->value('id');
        $emailChannelId = \App\Models\NotificationChannel::query()->where('code', 'email')->value('id');

        \App\Models\NotificationPreference::query()->create([
            'user_id' => $member->id,
            'notification_type_id' => $trainingTypeId,
            'notification_channel_id' => $emailChannelId,
            'enabled' => false,
            'frequency' => 'immediate',
        ]);

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'training_assigned',
            'queue' => false,
            'channels' => ['email'],
            'recipients' => [$member->id],
            'priority' => 'medium',
            'mail' => [
                'subject' => 'Training assigned',
                'lines' => ['Open your course.'],
            ],
        ]);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $member->id,
            'channel' => 'email',
            'status' => 'suppressed',
        ]);
    }
}
