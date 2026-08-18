<?php

namespace Tests\Feature;

use App\Livewire\Messaging\ConversationPanel;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\MessagingModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_messaging_center(): void
    {
        $this->seedMessaging();

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($member)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Communication Center', false)
            ->assertSeeText('Messages & Collaboration');
    }

    public function test_member_can_send_message_in_existing_conversation(): void
    {
        $this->seedMessaging();

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $conversation = Conversation::query()->where('type', 'direct')->firstOrFail();

        Livewire::actingAs($member)
            ->test(ConversationPanel::class, ['conversationId' => $conversation->id])
            ->set('messageBody', 'Can we review my licensing checklist tomorrow?')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'body' => 'Can we review my licensing checklist tomorrow?',
        ]);
    }

    public function test_cfm_can_start_direct_conversation_with_trainee(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $cfm = User::where('email', 'calendar-cfm@gwtrack.com')->firstOrFail();
        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $member->update(['mentor_id' => $cfm->id]);

        $conversation = app(MessagingService::class)->findOrCreateDirectConversation($cfm, $member);

        $this->assertSame('direct', $conversation->type);
        $this->assertCount(2, $conversation->activeMembers);
    }

    public function test_user_without_role_gets_403_until_member_role_is_assigned(): void
    {
        $this->seedMessaging();

        $member = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $member->syncRoles([]);

        $this->actingAs($member)
            ->get(route('messages.index'))
            ->assertForbidden();

        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('messages.index'))
            ->assertOk();
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
