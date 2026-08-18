<?php

namespace Tests\Feature;

use App\Livewire\Members\MyInvitesPage;
use App\Models\RegistrationInvitation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyInvitesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_my_invites_with_signup_and_unused_counts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sponsor = User::factory()->create(['is_active' => true]);
        $sponsor->assignRole('member');

        $acceptedUser = User::factory()->create([
            'name' => 'Signed Up Recruit',
            'email' => 'signed.up@example.com',
        ]);

        $unused = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'unused@example.com',
            'code' => 'UNUSEDCODE12',
        ]);

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'signed.up@example.com',
            'accepted_by' => $acceptedUser->id,
            'accepted_at' => now(),
            'uses_count' => 1,
        ]);

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'expired@example.com',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($sponsor)
            ->get(route('invites.index'))
            ->assertOk()
            ->assertSeeLivewire(MyInvitesPage::class)
            ->assertSee('My Invites', false)
            ->assertSee('Unused', false)
            ->assertSee('Signed up', false)
            ->assertSee($unused->code, false)
            ->assertSee('Never used yet', false)
            ->assertSee('Signed Up Recruit', false)
            ->assertSee('unused@example.com', false)
            ->assertSee('View Profile', false)
            ->assertSee(route('team.member.profile', $acceptedUser), false);
    }

    public function test_my_invites_page_filters_unused_links(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sponsor = User::factory()->create(['is_active' => true]);
        $sponsor->assignRole('member');

        $unused = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'still.open@example.com',
            'code' => 'STILLOPEN001',
        ]);

        $acceptedUser = User::factory()->create(['email' => 'done@example.com']);
        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'done@example.com',
            'accepted_by' => $acceptedUser->id,
            'accepted_at' => now(),
            'uses_count' => 1,
            'code' => 'ALREADYUSED1',
        ]);

        Livewire::actingAs($sponsor)
            ->test(MyInvitesPage::class)
            ->set('status', 'unused')
            ->assertSee('STILLOPEN001', false)
            ->assertSee('Never used yet', false)
            ->assertDontSee('ALREADYUSED1', false)
            ->assertViewHas('stats', function (array $stats): bool {
                return ($stats['unused'] ?? null) === 1
                    && ($stats['signed_up'] ?? null) === 1;
            });
    }

    public function test_inactive_member_cannot_open_my_invites(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(MyInvitesPage::class)
            ->assertForbidden();
    }

    public function test_member_can_create_invitation_from_my_invites_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sponsor = User::factory()->create(['is_active' => true]);
        $sponsor->assignRole('member');

        Livewire::actingAs($sponsor)
            ->test(MyInvitesPage::class)
            ->set('inviteEmail', 'new.recruit@example.com')
            ->call('createInvitation')
            ->assertHasNoErrors()
            ->assertSet('statusMessage', 'Invitation link created. Share it with your recruit.')
            ->assertNotSet('latestInvitationUrl', null);

        $this->assertDatabaseHas('registration_invitations', [
            'sponsor_id' => $sponsor->id,
            'email' => 'new.recruit@example.com',
        ]);
    }

    public function test_deleted_invitation_is_removed_from_invitation_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sponsor = User::factory()->create(['is_active' => true]);
        $sponsor->assignRole('member');

        $invitation = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'to.delete@example.com',
            'code' => 'DELETEMECODE1',
        ]);

        Livewire::actingAs($sponsor)
            ->test(MyInvitesPage::class)
            ->assertSee('DELETEMECODE1', false)
            ->call('destroyInvitation', $invitation->id)
            ->assertSet('statusMessage', 'Invitation link deleted.')
            ->assertDontSee('DELETEMECODE1', false);

        $this->assertNotNull($invitation->fresh()->revoked_at);
    }
}
