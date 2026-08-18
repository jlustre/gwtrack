<?php

namespace Tests\Feature;

use App\Livewire\Notifications\NotificationBell;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Notifications\NotificationPreferences;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCenterLivewireTest extends TestCase
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

    public function test_member_can_open_livewire_notification_center(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'training_assigned',
            'queue' => false,
            'recipients' => [$member->id],
            'title' => 'Training assigned',
            'message' => 'Complete Product Knowledge Foundations this week.',
            'priority' => 'medium',
        ]);

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notification Center', false)
            ->assertSee('Training assigned', false);
    }

    public function test_notification_center_can_mark_read_and_archive_without_page_reload(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $notification = app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'goal_reminder',
            'queue' => false,
            'recipients' => [$member->id],
            'title' => 'Goal reminder',
            'message' => 'Check in on your weekly prospecting goal.',
            'priority' => 'medium',
        ])->first();

        Livewire::actingAs($member)
            ->test(NotificationCenter::class)
            ->assertSee('Goal reminder', false)
            ->call('markRead', $notification->id)
            ->call('archive', $notification->id)
            ->assertDontSee('Goal reminder', false);

        $record = Notification::query()->findOrFail($notification->id);
        $this->assertNotNull($record->read_at);
        $this->assertNotNull($record->archived_at);
    }

    public function test_notification_bell_shows_unread_count(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        app(NotificationOrchestrator::class)->deliver([
            'trigger_code' => 'training_assigned',
            'queue' => false,
            'recipients' => [$member->id],
            'title' => 'Unread alert',
            'message' => 'Something needs attention.',
        ]);

        Livewire::actingAs($member)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 1)
            ->assertSee('Unread alert', false);
    }

    public function test_member_can_save_notification_preferences(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        Livewire::actingAs($member)
            ->test(NotificationPreferences::class)
            ->assertSee('Notification preferences', false)
            ->call('save');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $member->id,
        ]);
    }
}
