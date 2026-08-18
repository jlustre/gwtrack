<?php

namespace Tests\Feature;

use App\Models\NotificationDeliveryLog;
use App\Models\User;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notification_dashboard_is_accessible(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Notification operations')
            ->assertSeeLivewire('admin.notifications.admin-notification-dashboard');
    }

    public function test_admin_can_view_delivery_logs_and_resend_failed_entry(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();

        $log = NotificationDeliveryLog::query()->create([
            'notification_id' => null,
            'user_id' => $member->id,
            'trigger_code' => 'announcement_published',
            'channel' => 'email',
            'status' => 'failed',
            'failure_reason' => 'SMTP timeout',
            'attempted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.delivery-logs'))
            ->assertOk()
            ->assertSee('announcement_published')
            ->assertSee('SMTP timeout');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Notifications\NotificationDeliveryLogs::class)
            ->call('resend', $log->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notification_delivery_logs', [
            'trigger_code' => 'announcement_published',
            'status' => 'queued',
            'user_id' => $member->id,
        ]);
    }

    public function test_admin_can_send_test_notification_from_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(NotificationConfigSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $triggerId = DB::table('notification_triggers')->where('code', 'announcement_published')->value('id');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Notifications\AdminNotificationDashboard::class)
            ->set('testTriggerId', $triggerId)
            ->call('sendTestNotification')
            ->assertHasNoErrors();
    }
}
