<?php

namespace Tests\Feature\Auth;

use App\Models\Rank;
use App\Models\RegistrationInvitation;
use App\Models\Team;
use App\Models\User;
use App\Support\LocationOptions;
use App\Services\MonetizationSettingsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);
    }

    /**
     * @return array{country_id: int, state_province_id: int, timezone_id: int}
     */
    private function registrationLocationIds(): array
    {
        $countryId = LocationOptions::resolveCountryId('Canada');
        $stateProvinceId = LocationOptions::resolveStateProvinceId('Canada', 'British Columbia');
        $timezoneId = LocationOptions::resolveTimezoneId('Canada Pacific Time');

        $this->assertNotNull($countryId);
        $this->assertNotNull($stateProvinceId);
        $this->assertNotNull($timezoneId);

        return [
            'country_id' => $countryId,
            'state_province_id' => $stateProvinceId,
            'timezone_id' => $timezoneId,
        ];
    }

    public function test_open_registration_screen_is_blocked_without_invitation(): void
    {
        $response = $this->get('/register');

        $response->assertForbidden();
    }

    public function test_registration_screen_can_be_rendered_with_valid_invitation(): void
    {
        $invitation = RegistrationInvitation::factory()->create();

        $response = $this->get(route('register.invitation', $invitation->code));

        $response
            ->assertStatus(200)
            ->assertSee($invitation->code)
            ->assertSee($invitation->sponsor->name)
            ->assertSee('Important Before Registering')
            ->assertSee('You must already be registered with Greatway Financial Group before completing GWTrack registration.')
            ->assertSee('If you have not finished your Greatway enrollment, ask your sponsor how to proceed.')
            ->assertSee('Your Greatway sponsor must be the same person as your GWTrack sponsor')
            ->assertSee('If this is not the person who invited you, stop here and ask the correct sponsor to send their invitation link.')
            ->assertDontSee('Verified Sponsor')
            ->assertSee('State / Province')
            ->assertSee('British Columbia', false)
            ->assertSee('California', false);
    }

    public function test_registration_screen_shows_unavailable_message_for_unknown_invitation(): void
    {
        $response = $this->get(route('register.invitation', 'UNKNOWNCODE'));

        $response->assertForbidden();
    }

    public function test_new_users_can_register_with_valid_invitation(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $team = Team::create([
            'owner_id' => $sponsor->id,
            'leader_id' => $sponsor->id,
            'name' => 'West Coast Builders',
            'is_active' => true,
        ]);
        $sponsor->forceFill(['team_id' => $team->id])->save();

        $invitation = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'code' => 'ABC123GW',
        ]);

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'gw_associate_id' => 'GW-1001',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();

        $newUser = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame($sponsor->id, $newUser->sponsor_id);
        $this->assertSame($team->id, $newUser->team_id);
        $this->assertSame(Rank::where('code', 'TA')->firstOrFail()->id, $newUser->rank_id);
        $this->assertSame('Test User', $newUser->name);
        $this->assertTrue($newUser->hasRole('member'));
        $this->assertNotNull($newUser->joined_at);
        $this->assertNull($newUser->last_login_at);
        $this->assertNull($newUser->last_login_ip);
        $this->assertFalse($newUser->is_online);
        $this->assertNull($newUser->email_verified_at);
        $this->assertSame('GW-1001', $newUser->profile->gw_associate_id);
        $this->assertSame('Vancouver', $newUser->profile->city);
        $newUser->load('profile.countryRecord', 'profile.stateProvince', 'profile.timezoneRecord');
        $this->assertSame('British Columbia', $newUser->profile->province);
        $this->assertSame('Canada', $newUser->profile->country);
        $this->assertNotNull($newUser->profile->country_id);
        $this->assertNotNull($newUser->profile->state_province_id);
        $this->assertSame('Canada|British Columbia', \App\Support\LocationOptions::jurisdictionKey(
            $newUser->profile->country,
            $newUser->profile->province,
        ));
        $this->assertSame('Canada Pacific Time', $newUser->profile->timezone);
        $this->assertTrue($newUser->profile->is_gw_active_associate);
        $this->assertDatabaseHas('registration_invitations', [
            'id' => $invitation->id,
            'accepted_by' => $newUser->id,
            'uses_count' => 1,
        ]);
        $this->assertNotNull($invitation->refresh()->revoked_at);

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status');

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email')
            ->assertRedirect(route('verification.resend', ['email' => 'test@example.com'], false));

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $newUser->id, 'hash' => sha1($newUser->email)]
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status');

        $this->assertNotNull($newUser->fresh()->email_verified_at);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('show_profile_completion_modal', true);

        $this->actingAs($newUser->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('forceProfileCompletionModal', true)
            ->assertSee('Complete your profile', false)
            ->assertSee('Profile completion', false)
            ->assertSee('Required fields', false);
    }

    public function test_email_bound_invitation_rejects_a_different_email(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $invitation = RegistrationInvitation::factory()->create([
            'email' => 'invited.member@example.com',
        ]);

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'someone.else@example.com',
            'gw_associate_id' => 'GW-LOCKED-1',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'someone.else@example.com',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'invited.member@example.com',
        ]);
    }

    public function test_email_bound_invitation_locks_email_on_registration_form(): void
    {
        $invitation = RegistrationInvitation::factory()->create([
            'email' => 'locked.invitee@example.com',
        ]);

        $this->get(route('register.invitation', $invitation->code))
            ->assertOk()
            ->assertSee('locked.invitee@example.com', false)
            ->assertSee('This invitation is locked to', false)
            ->assertSee('cannot be used with another email', false);
    }

    public function test_email_bound_invitation_allows_the_invitee_email(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $invitation = RegistrationInvitation::factory()->create([
            'email' => 'invited.member@example.com',
        ]);

        $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Invited',
            'last_name' => 'Member',
            'email' => 'invited.member@example.com',
            'gw_associate_id' => 'GW-LOCKED-2',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'invited.member@example.com',
            'sponsor_id' => $invitation->sponsor_id,
        ]);
    }

    public function test_registration_requires_sponsor_and_active_associate_confirmation(): void
    {
        $invitation = RegistrationInvitation::factory()->create();

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'gw_associate_id' => 'GW-1002',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'sponsor_confirmed',
            'active_associate_confirmed',
        ]);

        $this->assertGuest();
    }

    public function test_invitation_code_cannot_be_reused_after_acceptance(): void
    {
        $invitation = RegistrationInvitation::factory()->create([
            'uses_count' => 1,
            'max_uses' => 1,
        ]);

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'gw_associate_id' => 'GW-1003',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasErrors('registration_code');

        $this->assertGuest();
    }

    public function test_registration_requires_country_and_state_province(): void
    {
        $invitation = RegistrationInvitation::factory()->create();

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'missing-location@example.com',
            'gw_associate_id' => 'GW-1004',
            'city' => 'Vancouver',
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'country_id',
            'state_province_id',
            'timezone_id',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'missing-location@example.com',
        ]);
    }

    public function test_registration_shows_subscription_terms_when_monetization_is_enabled(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $invitation = RegistrationInvitation::factory()->create();

        $this->get(route('register.invitation', $invitation->code))
            ->assertOk()
            ->assertSee('GWTrack subscription charges')
            ->assertSee('Senior Associate (SA)')
            ->assertSee('$9.95')
            ->assertSee('I agree to these charges')
            ->assertSee('Agreement required before you can register');
    }

    public function test_registration_requires_subscription_agreement_when_monetization_is_enabled(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
        ]);

        app(MonetizationSettingsService::class)->save(true, []);

        $invitation = RegistrationInvitation::factory()->create();

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'billing@example.com',
            'gw_associate_id' => 'GW-BILL-1',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('subscription_terms_accepted');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'billing@example.com',
        ]);
    }

    public function test_registration_succeeds_after_subscription_agreement_when_monetization_is_enabled(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        app(MonetizationSettingsService::class)->save(true, []);

        $invitation = RegistrationInvitation::factory()->create();

        $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'agreed@example.com',
            'gw_associate_id' => 'GW-BILL-2',
            'city' => 'Vancouver',
            ...$this->registrationLocationIds(),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'subscription_terms_accepted' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login', absolute: false));

        $user = User::where('email', 'agreed@example.com')->firstOrFail();
        $this->assertNotNull($user->profile->subscription_terms_accepted_at);
    }
}
