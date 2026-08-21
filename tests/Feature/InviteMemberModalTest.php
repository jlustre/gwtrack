<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskController;
use App\Livewire\Dashboard\MyTasksModal;
use App\Livewire\Members\InviteMemberModal;
use App\Livewire\Dashboard\OpenTaskCountBadge;
use App\Mail\InvitationLinkMail;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\Members\MemberInvitationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class InviteMemberModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_bar_shows_invite_member_for_active_members(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Invite Member', false)
            ->assertSee('Invite New Member', false)
            ->assertSee('open-invite-member-modal', false);
    }

    public function test_inactive_member_cannot_create_invitations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('member');

        $this->assertFalse(app(MemberInvitationService::class)->canCreateInvitation($user));

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal')
            ->assertForbidden();
    }

    public function test_member_can_generate_invitation_from_modal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal')
            ->assertSet('show', true)
            ->set('email', 'new.member@example.com')
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_invitations', [
            'sponsor_id' => $user->id,
            'email' => 'new.member@example.com',
            'role_name' => 'member',
            'max_uses' => 1,
        ]);

        $component = Livewire::actingAs($user)->test(InviteMemberModal::class);
        $component->set('invitationUrl', 'https://example.test/register/ABC')
            ->set('invitationCode', 'ABC123')
            ->call('startAnother')
            ->assertSet('invitationUrl', null)
            ->assertSet('invitationCode', null);
    }

    public function test_modal_opens_with_existing_pending_invitation(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $invitation = app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal', invitationId: $invitation->id)
            ->assertSet('show', true)
            ->assertSet('loadedExistingInvitation', true)
            ->assertSet('invitationId', $invitation->id)
            ->assertSet('invitationCode', $invitation->code)
            ->assertSet('recipientEmail', 'pending.recruit@example.com');
    }

    public function test_sending_invitation_email_removes_task_from_open_my_tasks_list(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $invitation = app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('Send invitation email');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal', invitationId: $invitation->id)
            ->set('recipientEmail', 'pending.recruit@example.com')
            ->call('sendEmail')
            ->assertHasNoErrors()
            ->assertSet('show', false);

        $payload = app(\App\Http\Controllers\TaskController::class)->openTasksByPriorityFor($user);

        $this->assertNull(collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_my_tasks_refreshes_after_invitation_email_sent_event(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $invitation = app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        $component = Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('Send invitation email');

        app(MemberInvitationService::class)->sendInvitationEmail(
            $user,
            $invitation->fresh(),
            'pending.recruit@example.com',
        );

        $component
            ->dispatch('invitation-email-sent')
            ->assertDontSee('Send invitation email')
            ->assertSee('Invitation email sent. Task completed.');
    }

    public function test_open_task_count_badge_updates_after_invitation_email_sent(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $invitation = app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        $beforeCount = app(TaskController::class)->openTaskCountFor($user);

        $badge = Livewire::actingAs($user)
            ->test(OpenTaskCountBadge::class)
            ->assertSet('count', $beforeCount);

        app(MemberInvitationService::class)->sendInvitationEmail(
            $user,
            $invitation->fresh(),
            'pending.recruit@example.com',
        );

        $badge->dispatch('invitation-email-sent')
            ->assertSet('count', $beforeCount - 1);
    }

    public function test_delete_invitation_from_modal_removes_email_task(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        $invitation = app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('Send invitation email');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal', invitationId: $invitation->id)
            ->call('deleteInvitation')
            ->assertHasNoErrors()
            ->assertSet('show', false);

        $this->assertNotNull($invitation->fresh()->revoked_at);

        $payload = app(TaskController::class)->openTasksByPriorityFor($user);

        $this->assertNull(collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_my_tasks_refreshes_after_invitation_deleted_event(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        app(MemberInvitationService::class)->createInvitation($user, 'pending.recruit@example.com');

        $component = Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('Send invitation email');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal', invitationId: RegistrationInvitation::query()->value('id'))
            ->call('deleteInvitation');

        $component
            ->dispatch('invitation-deleted')
            ->assertDontSee('Send invitation email')
            ->assertSee('Invitation link deleted. Task completed.');
    }

    public function test_email_link_requires_recipient_email_after_invite_is_generated(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal')
            ->call('generate')
            ->assertHasNoErrors()
            ->set('recipientEmail', '')
            ->call('sendEmail')
            ->assertHasErrors(['recipientEmail' => 'required']);
    }

    public function test_member_can_email_invitation_link_from_modal(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(InviteMemberModal::class)
            ->dispatch('open-invite-member-modal')
            ->call('generate')
            ->set('recipientEmail', 'invitee@example.com')
            ->call('sendEmail')
            ->assertHasNoErrors()
            ->assertSet('emailSentMessage', 'Invitation email sent to invitee@example.com.');

        Mail::assertSent(InvitationLinkMail::class, function (InvitationLinkMail $mail) use ($user): bool {
            return $mail->hasTo('invitee@example.com')
                && $mail->senderEmail === $user->email;
        });

        $this->assertDatabaseHas('registration_invitations', [
            'sponsor_id' => $user->id,
            'email' => 'invitee@example.com',
        ]);
    }
}
