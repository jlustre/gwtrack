<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\NotificationDemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_config_seeder_creates_types_triggers_and_templates(): void
    {
        $this->seed(NotificationConfigSeeder::class);

        $this->assertDatabaseCount('notification_types', 27);
        $this->assertDatabaseCount('notification_triggers', 51);
        $this->assertDatabaseCount('notification_templates', 51);

        $this->assertDatabaseHas('notification_types', [
            'code' => 'training',
            'name' => 'Training',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('notification_triggers', [
            'code' => 'mentor_assigned',
            'event_key' => 'mentor.assigned',
        ]);

        $this->assertDatabaseHas('notification_templates', [
            'name' => 'Default Mentor Assigned',
            'subject' => 'Mentor assigned',
            'is_default' => true,
        ]);
    }

    public function test_notification_demo_seeder_creates_sample_notifications(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->seed(NotificationDemoSeeder::class);

        $this->assertGreaterThanOrEqual(3, DB::table('notifications')->count());

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $member->id,
            'sender_type' => 'system',
        ]);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $member->id)
            ->whereNotNull('trigger_id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertNotNull($notification->notification_type_id);
        $this->assertNotNull($notification->notification_template);
        $this->assertNotNull($notification->action_link);
    }

    public function test_admin_can_manage_notification_configuration_tables(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.management.index', ['category' => 'notifications']))
            ->assertOk()
            ->assertSee('Notification Types')
            ->assertSee('Notification Triggers')
            ->assertSee('Notification Templates')
            ->assertSee('Notifications');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'notification-types'))
            ->assertOk()
            ->assertSee('Training');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'notification-types'), [
                'code' => 'recognition_custom',
                'name' => 'Recognition Custom',
                'description' => 'Badge and recognition alerts.',
                'icon' => 'trophy',
                'color' => '#84BB41',
                'sort_order' => 60,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $typeId = DB::table('notification_types')->where('code', 'recognition_custom')->value('id');

        $this->assertNotNull($typeId);

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'notification-triggers'), [
                'notification_type_id' => $typeId,
                'code' => 'badge_awarded',
                'name' => 'Badge Awarded',
                'description' => 'Fires when a member earns a badge.',
                'event_key' => 'badge.awarded',
                'sort_order' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $triggerId = DB::table('notification_triggers')->where('code', 'badge_awarded')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'notification-templates'), [
                'notification_trigger_id' => $triggerId,
                'name' => 'Default Badge Awarded',
                'subject' => 'Badge earned',
                'body' => '{{ member_name }} earned the {{ badge_name }} badge.',
                'channels' => '["in_app"]',
                'placeholders' => '["member_name","badge_name"]',
                'is_default' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_templates', [
            'notification_trigger_id' => $triggerId,
            'name' => 'Default Badge Awarded',
            'is_default' => true,
        ]);
    }

    public function test_admin_can_create_notification_record_with_uuid(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $typeId = DB::table('notification_types')->where('code', 'system')->value('id');
        $triggerId = DB::table('notification_triggers')->where('code', 'announcement_published')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'notifications'), [
                'notification_type_id' => $typeId,
                'trigger_id' => $triggerId,
                'sender_type' => 'system',
                'notifiable_id' => $member->id,
                'data' => json_encode([
                    'title' => 'Portal maintenance',
                    'message' => 'Scheduled maintenance this weekend.',
                    'category' => 'System',
                ]),
                'recipients' => json_encode(['user_ids' => [$member->id]]),
                'action_link' => json_encode(['label' => 'View dashboard', 'url' => '/dashboard']),
            ])
            ->assertRedirect();

        $notification = DB::table('notifications')
            ->where('notifiable_id', $member->id)
            ->where('trigger_id', $triggerId)
            ->first();

        $this->assertNotNull($notification);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $notification->id);
        $this->assertSame(User::class, $notification->notifiable_type);
        $this->assertSame('system', $notification->sender_type);
    }
}
