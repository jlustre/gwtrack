<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectCreate;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectQualificationTraitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);
    }

    public function test_create_form_renders_qualification_trait_checkboxes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('team.prospects.create'))
            ->assertOk()
            ->assertSee('Qualification Traits', false)
            ->assertSee('25+ years old', false)
            ->assertSee('W/ mortgage', false)
            ->assertSee('Business minded', false);
    }

    public function test_prospect_create_persists_selected_qualification_traits(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(ProspectCreate::class)
            ->set('first_name', 'Trait')
            ->set('last_name', 'Tester')
            ->set('qualification_traits', ['age_25_plus', 'married', 'has_mortgage'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $prospect = Prospect::query()->where('first_name', 'Trait')->firstOrFail();

        $this->assertSame(['age_25_plus', 'married', 'has_mortgage'], $prospect->qualification_traits);
    }

    public function test_edit_form_shows_saved_qualification_traits(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Saved',
            'last_name' => 'Traits',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'qualification_traits' => ['legal_resident', 'business_minded'],
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects.records.edit', $prospect))
            ->assertOk()
            ->assertSee('Qualification Traits', false)
            ->assertSee('value="legal_resident"', false)
            ->assertSee('value="business_minded"', false);
    }

    public function test_prospect_create_persists_qualification_profile_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(ProspectCreate::class)
            ->set('first_name', 'Profile')
            ->set('last_name', 'Tester')
            ->set('date_of_birth', '1985-03-15')
            ->set('occupation', 'Nurse')
            ->set('employer_business', 'City Hospital')
            ->set('marital_status', 'married')
            ->set('spouse_name', 'Jamie Tester')
            ->set('spouse_occupation', 'Teacher')
            ->set('spouse_date_of_birth', '1987-08-20')
            ->set('dependents', [
                ['name' => 'Alex', 'age' => 8],
                ['name' => 'Sam', 'age' => 5],
            ])
            ->set('qualification_notes', 'Interested in term life and college savings.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $prospect = Prospect::query()->where('first_name', 'Profile')->firstOrFail();

        $this->assertSame('1985-03-15', $prospect->date_of_birth?->format('Y-m-d'));
        $this->assertSame('Nurse', $prospect->occupation);
        $this->assertSame('City Hospital', $prospect->employer_business);
        $this->assertSame('married', $prospect->marital_status);
        $this->assertSame('Jamie Tester', $prospect->spouse_name);
        $this->assertSame('Teacher', $prospect->spouse_occupation);
        $this->assertSame('1987-08-20', $prospect->spouse_date_of_birth?->format('Y-m-d'));
        $this->assertSame([
            ['name' => 'Alex', 'age' => 8],
            ['name' => 'Sam', 'age' => 5],
        ], $prospect->dependents);
        $this->assertSame('Interested in term life and college savings.', $prospect->qualification_notes);
    }

    public function test_edit_form_updates_qualification_profile_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Edit',
            'last_name' => 'Profile',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        $this->actingAs($user)
            ->patch(route('team.prospects.records.update', $prospect), [
                'first_name' => 'Edit',
                'last_name' => 'Profile',
                'funnel_type' => 'insurance',
                'status' => 'active',
                'interest_level' => 'warm',
                'priority' => 'medium',
                'occupation' => 'Engineer',
                'date_of_birth' => '1990-05-01',
                'spouse_name' => 'Taylor Profile',
                'dependents' => [
                    ['name' => 'Riley', 'age' => 10],
                ],
                'qualification_notes' => 'Looking for mortgage protection.',
            ])
            ->assertRedirect(route('team.prospects.records.show', $prospect));

        $prospect->refresh();

        $this->assertSame('Engineer', $prospect->occupation);
        $this->assertSame('1990-05-01', $prospect->date_of_birth?->format('Y-m-d'));
        $this->assertSame('Taylor Profile', $prospect->spouse_name);
        $this->assertSame([
            ['name' => 'Riley', 'age' => 10],
        ], $prospect->dependents);
        $this->assertSame('Looking for mortgage protection.', $prospect->qualification_notes);
    }
}
