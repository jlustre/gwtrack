<?php

namespace Tests\Feature;

use App\Livewire\Messaging\ConversationPanel;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\MessagingModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_center_shows_business_use_warning(): void
    {
        $this->seedMessaging();

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($member)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Business use only', false)
            ->assertSee('business-related topics only', false);
    }

    public function test_suspended_member_cannot_send_messages(): void
    {
        $this->seedMessaging();

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $conversation = Conversation::query()->where('type', 'direct')->firstOrFail();

        $member->forceFill([
            'messaging_suspended_at' => now(),
            'messaging_suspension_reason' => 'Non-business personal chat.',
        ])->save();

        $this->actingAs($member)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Messaging access suspended', false)
            ->assertSee('Non-business personal chat.', false);

        Livewire::actingAs($member)
            ->test(ConversationPanel::class, [
                'conversationId' => $conversation->id,
                'isMessagingSuspended' => true,
            ])
            ->set('messageBody', 'Hello again')
            ->call('sendMessage')
            ->assertForbidden();
    }

    public function test_admin_can_suspend_and_restore_member_messaging(): void
    {
        $this->seedMessaging();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.users.messaging.suspend', $member), [
                'messaging_suspension_reason' => 'Off-topic personal messages.',
            ])
            ->assertRedirect(route('admin.users.edit', $member));

        $member->refresh();
        $this->assertTrue($member->isMessagingSuspended());
        $this->assertSame('Off-topic personal messages.', $member->messaging_suspension_reason);
        $this->assertSame($admin->id, $member->messaging_suspended_by);

        $this->actingAs($admin)
            ->patch(route('admin.users.messaging.restore', $member))
            ->assertRedirect(route('admin.users.edit', $member));

        $member->refresh();
        $this->assertFalse($member->isMessagingSuspended());
        $this->assertNull($member->messaging_suspension_reason);
    }

    private function seedMessaging(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
            MessagingModuleSeeder::class,
        ]);
    }
}
