<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Profile;
use App\Models\StateProvince;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssociateParticipationAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_prefills_from_user_and_profile_tables(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sponsor = User::factory()->create(['name' => 'Sponsor Leader']);
        $country = Country::query()->create(['name' => 'Canada', 'code' => 'CA', 'sort_order' => 1]);
        $province = StateProvince::query()->create([
            'country_id' => $country->id,
            'name' => 'Ontario',
            'code' => 'ON',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Jane Associate',
            'email' => 'jane@example.com',
            'sponsor_id' => $sponsor->id,
            'joined_at' => '2026-01-15 10:00:00',
        ]);
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'phone' => '555-0100',
            'city' => 'Toronto',
            'country_id' => $country->id,
            'state_province_id' => $province->id,
            'gw_associate_id' => 'GW-12345',
        ]);

        $this->actingAs($user)
            ->get(route('resources.forms.associate-participation-agreement'))
            ->assertOk()
            ->assertSee('Jane Associate', false)
            ->assertSee('jane@example.com', false)
            ->assertSee('555-0100', false)
            ->assertSee('GW-12345', false)
            ->assertSee('Toronto', false)
            ->assertSee('Ontario', false)
            ->assertSee('Canada', false)
            ->assertSee('Sponsor Leader', false)
            ->assertSee('2026-01-15', false)
            ->assertSee('pre-filled from your account profile', false);
    }

    public function test_member_can_submit_prefilled_agreement(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $user->assignRole('member');

        Profile::query()->create([
            'user_id' => $user->id,
            'phone' => '555-9999',
            'city' => 'Calgary',
            'gw_associate_id' => 'GW-999',
        ]);

        $this->actingAs($user)
            ->post(route('resources.forms.associate-participation-agreement.store'), [
                'effective_date' => '2026-06-04',
                'full_name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '555-9999',
                'associate_id' => 'GW-999',
                'address' => '123 Main St',
                'city' => 'Calgary',
                'state_province' => 'Alberta',
                'country' => 'Canada',
                'sponsor_name' => '',
                'acknowledgment_accepted' => '1',
                'associate_signature' => 'John Doe',
                'associate_signed_at' => '2026-06-04',
            ])
            ->assertRedirect(route('resources.forms.associate-participation-agreement'));

        $this->assertDatabaseHas('associate_participation_agreements', [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'submitted',
        ]);

        $agreement = \App\Models\AssociateParticipationAgreement::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($agreement->pdf_path);
        Storage::disk('public')->assertExists($agreement->pdf_path);
    }

    public function test_submitted_agreement_cannot_be_resubmitted(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id, 'phone' => '555-9999', 'city' => 'Calgary']);

        $payload = [
            'effective_date' => '2026-06-04',
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-9999',
            'associate_id' => 'GW-999',
            'address' => '123 Main St',
            'city' => 'Calgary',
            'state_province' => 'Alberta',
            'country' => 'Canada',
            'sponsor_name' => '',
            'acknowledgment_accepted' => '1',
            'associate_signature' => 'John Doe',
            'associate_signed_at' => '2026-06-04',
        ];

        $this->actingAs($user)
            ->post(route('resources.forms.associate-participation-agreement.store'), $payload)
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('resources.forms.associate-participation-agreement.store'), array_merge($payload, [
                'full_name' => 'Changed Name',
            ]))
            ->assertSessionHasErrors('form');

        $this->assertSame('John Doe', \App\Models\AssociateParticipationAgreement::query()->where('user_id', $user->id)->value('full_name'));
    }

    public function test_submitted_member_can_download_pdf(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id, 'phone' => '555-9999', 'city' => 'Calgary']);

        $this->actingAs($user)
            ->post(route('resources.forms.associate-participation-agreement.store'), [
                'effective_date' => '2026-06-04',
                'full_name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '555-9999',
                'associate_id' => 'GW-999',
                'address' => '123 Main St',
                'city' => 'Calgary',
                'state_province' => 'Alberta',
                'country' => 'Canada',
                'sponsor_name' => '',
                'acknowledgment_accepted' => '1',
                'associate_signature' => 'John Doe',
                'associate_signed_at' => '2026-06-04',
            ]);

        $this->actingAs($user)
            ->get(route('resources.forms.associate-participation-agreement.download'))
            ->assertOk()
            ->assertDownload('associate-participation-agreement.pdf');
    }

    public function test_seeded_document_links_to_interactive_form(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            \Database\Seeders\ResourceDocumentSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = \App\Models\PortalResource::query()
            ->where('title', 'Associate Participation Agreement')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('resources/forms/associate-participation-agreement', $document->url);

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('Associate Participation Agreement', false)
            ->assertSee('Fill Form', false)
            ->assertSee(route('resources.forms.associate-participation-agreement'), false);
    }

    public function test_guest_cannot_access_agreement_form(): void
    {
        $this->get(route('resources.forms.associate-participation-agreement'))
            ->assertRedirect(route('login'));
    }
}
