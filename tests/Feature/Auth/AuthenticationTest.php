<?php

namespace Tests\Feature\Auth;

use App\Models\Profile;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
        $this->assertTrue($user->is_online);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_receive_member_role_by_default(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'email' => 'default.member@example.com',
        ]);

        $this->assertTrue($user->fresh()->hasRole(User::DEFAULT_ROLE));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_roleless_users_receive_member_role_on_login_and_can_open_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'email' => 'roleless.member@example.com',
        ]);

        $user->syncRoles([]);
        $this->assertFalse($user->fresh()->hasRole(User::DEFAULT_ROLE));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue($user->fresh()->hasRole(User::DEFAULT_ROLE));

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_unverified_users_can_not_authenticate(): void
    {
        $user = User::factory()->unverified()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email')
            ->assertRedirect(route('verification.resend', ['email' => $user->email], false));

        $this->assertGuest();
    }

    public function test_login_prompts_profile_completion_modal_when_profile_is_incomplete(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Incomplete Member',
            'email' => 'incomplete@example.com',
        ]);
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'city' => 'Vancouver',
            'country_id' => LocationOptions::resolveCountryId('Canada'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('show_profile_completion_modal', true);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('forceProfileCompletionModal', true)
            ->assertSee('Complete your profile', false)
            ->assertSee('My Profile', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('forceProfileCompletionModal', false);
    }

    public function test_profile_completion_modal_persists_province_and_gw_fields(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Modal Member',
            'email' => 'modal@example.com',
        ]);
        $user->assignRole('member');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'redirect_to' => 'dashboard',
                'name' => 'Modal Member',
                'email' => 'modal@example.com',
                'city' => 'Toronto',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
                'gw_associate_id' => 'GW-MODAL-1',
                'gw_invite_link' => 'https://greatwayfinancial.com/invite/modal-1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh()->load('profile.stateProvince');

        $this->assertSame('Ontario', $user->profile->province);
        $this->assertSame('GW-MODAL-1', $user->profile->gw_associate_id);
        $this->assertSame('https://greatwayfinancial.com/invite/modal-1', $user->profile->gw_invite_link);

        $ontarioId = LocationOptions::resolveStateProvinceId('Canada', 'Ontario');

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('value="'.$ontarioId.'" selected', false);
    }

    public function test_profile_completion_modal_keeps_input_on_validation_error(): void
    {
        Storage::fake('public');

        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Modal Member',
            'email' => 'modal@example.com',
        ]);
        $user->assignRole('member');

        $photo = UploadedFile::fake()->image('profile.jpg');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'redirect_to' => 'dashboard',
                'name' => 'Modal Member',
                'email' => 'modal@example.com',
                'phone' => '416-555-0100',
                'city' => 'Toronto',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
                'gw_associate_id' => 'GW-MODAL-1',
                'gw_invite_link' => 'not-a-valid-url',
                'photo' => $photo,
            ])
            ->assertSessionHasErrors('gw_invite_link')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Complete your profile', false)
            ->assertSee('416-555-0100', false)
            ->assertSee('Toronto', false)
            ->assertSee('GW-MODAL-1', false)
            ->assertSee('not-a-valid-url', false);

        $stagedPath = session('profile_completion_staged_photo_path');
        $this->assertIsString($stagedPath);
        Storage::disk('public')->assertExists($stagedPath);

        $this->actingAs($user)
            ->get(route('profile.completion-photo-preview'))
            ->assertOk();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'redirect_to' => 'dashboard',
                'name' => 'Modal Member',
                'email' => 'modal@example.com',
                'phone' => '416-555-0100',
                'city' => 'Toronto',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
                'gw_associate_id' => 'GW-MODAL-1',
                'gw_invite_link' => 'https://greatwayfinancial.com/invite/modal-1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh()->load('profile');

        $this->assertSame('Toronto', $user->profile->city);
        $this->assertSame('416-555-0100', $user->profile->phone);
        $this->assertNotNull($user->profile->profile_photo_path);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $this->assertFalse($user->refresh()->is_online);
        $response->assertRedirect('/');
    }
}
