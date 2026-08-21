<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfilePage;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_livewire_component(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->syncRoles(['member']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeLivewire(ProfilePage::class)
            ->assertSee('Profile Details', false)
            ->assertSee('Save Profile', false);
    }

    public function test_livewire_can_switch_tabs_without_full_redirect(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('tab', 'licenses')
            ->assertSet('tab', 'licenses')
            ->assertSee('Insurance Licenses', false)
            ->set('tab', 'checklists')
            ->assertSet('tab', 'checklists')
            ->assertSee('Checklists', false);
    }

    public function test_livewire_can_save_profile_without_redirect(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('name', 'Livewire Profile User')
            ->set('email', 'livewire-profile@example.com')
            ->set('phone', '555-999-0000')
            ->set('city', 'Toronto')
            ->set('countryId', (string) LocationOptions::resolveCountryId('Canada'))
            ->set('stateProvinceId', (string) LocationOptions::resolveStateProvinceId('Canada', 'Ontario'))
            ->set('timezoneId', (string) LocationOptions::resolveTimezoneId('Canada Eastern Time'))
            ->set('bestContactTime', 'Morning (8am – 12pm)')
            ->set('bio', 'Updated via Livewire')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('profileFeedback.type', 'success')
            ->assertSet('tab', 'profile')
            ->assertNoRedirect();

        $user->refresh()->load('profile');

        $this->assertSame('Livewire Profile User', $user->name);
        $this->assertSame('livewire-profile@example.com', $user->email);
        $this->assertSame('555-999-0000', $user->profile->phone);
        $this->assertSame('Updated via Livewire', $user->profile->bio);
    }

    public function test_livewire_can_save_licenses_without_redirect(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('tab', 'licenses')
            ->set('isLicensedLifeInsurance', true)
            ->set('insuranceLicenses', [
                'Canada|Ontario',
                'United States|California',
            ])
            ->set('insuranceLicenseNumbers', [
                'Canada|Ontario' => 'ON-7777',
                'United States|California' => 'CA-8888',
            ])
            ->set('insuranceLicenseExpirations', [
                'Canada|Ontario' => '2027-06-30',
                'United States|California' => '2028-01-15',
            ])
            ->call('saveLicenses')
            ->assertHasNoErrors()
            ->assertSet('licensesFeedback.type', 'success')
            ->assertNoRedirect();

        $user->refresh();

        $this->assertTrue($user->profile->is_licensed_life_insurance);
        $this->assertSame(
            ['Canada|Ontario', 'United States|California'],
            $user->profile->insurance_licenses
        );
        $this->assertSame(
            [
                'Canada|Ontario' => 'ON-7777',
                'United States|California' => 'CA-8888',
            ],
            $user->profile->insurance_license_numbers
        );
        $this->assertSame(
            [
                'Canada|Ontario' => '2027-06-30',
                'United States|California' => '2028-01-15',
            ],
            $user->profile->insurance_license_expirations
        );
    }

    public function test_livewire_requires_a_license_number_for_each_selected_jurisdiction(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('tab', 'licenses')
            ->set('isLicensedLifeInsurance', true)
            ->set('insuranceLicenses', ['Canada|Ontario'])
            ->set('insuranceLicenseNumbers', ['Canada|Ontario' => ''])
            ->call('saveLicenses')
            ->assertHasErrors(['insuranceLicenseNumbers.Canada|Ontario'])
            ->assertSet('licensesFeedback.type', 'error');

        $this->assertNull($user->fresh()->profile?->insurance_licenses);
    }

    public function test_livewire_requires_an_expiration_date_for_each_selected_jurisdiction(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('tab', 'licenses')
            ->set('isLicensedLifeInsurance', true)
            ->set('insuranceLicenses', ['Canada|Ontario'])
            ->set('insuranceLicenseNumbers', ['Canada|Ontario' => 'ON-1001'])
            ->set('insuranceLicenseExpirations', ['Canada|Ontario' => ''])
            ->call('saveLicenses')
            ->assertHasErrors(['insuranceLicenseExpirations.Canada|Ontario'])
            ->assertSet('licensesFeedback.type', 'error');

        $this->assertNull($user->fresh()->profile?->insurance_licenses);
    }

    public function test_livewire_can_create_invitation_without_redirect(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('inviteEmail', 'invitee@example.com')
            ->call('createInvitation')
            ->assertHasNoErrors()
            ->assertSet('invitationStatus', 'invitation-created')
            ->assertSet('historyOpen', true)
            ->assertNotSet('latestInvitationUrl', null)
            ->assertNoRedirect();
    }
}
