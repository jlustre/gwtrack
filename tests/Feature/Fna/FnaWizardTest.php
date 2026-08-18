<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaWizard;
use App\Models\User;
use App\Services\Fna\DimeCalculatorService;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_autosaves_client_information(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($user, ['client_name' => 'Initial Name']);

        Livewire::actingAs($user)
            ->test(FnaWizard::class, ['fna' => $fna])
            ->assertSee('Select gender', false)
            ->assertSee('Select country', false)
            ->set('client_name', 'Updated Client')
            ->set('client_email', 'client@example.com')
            ->call('autosave')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fna_records', [
            'id' => $fna->id,
            'client_name' => 'Updated Client',
            'client_email' => 'client@example.com',
        ]);
    }

    public function test_wizard_saves_dime_analysis_and_updates_gap(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($user, ['client_name' => 'DIME Client']);

        Livewire::actingAs($user)
            ->test(FnaWizard::class, ['fna' => $fna])
            ->set('activeTab', 'dime')
            ->set('dime.credit_card_debt', 10000)
            ->set('dime.income_annual_to_replace', 60000)
            ->set('dime.income_years_to_replace', 10)
            ->set('dime.income_inflation_adjustment', false)
            ->set('dime.mortgage_balance', 200000)
            ->set('dime.include_mortgage_payoff', true)
            ->set('dime.education_children_count', 1)
            ->set('dime.education_cost_per_child', 50000)
            ->set('dime.education_inflation_adjustment', false)
            ->set('dime.existing_life_insurance', 100000)
            ->call('saveDime')
            ->assertHasNoErrors();

        $fna->refresh();

        $this->assertTrue($fna->dime_completed);
        $this->assertNotNull($fna->protection_gap);
        $this->assertDatabaseHas('fna_dime_analyses', ['fna_record_id' => $fna->id]);
    }

    public function test_dime_calculator_service_persists_to_record(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $fna = app(FnaRecordService::class)->create($user, ['client_name' => 'Persist Test']);

        app(DimeCalculatorService::class)->saveToFna($fna, [
            'credit_card_debt' => 5000,
            'income_annual_to_replace' => 50000,
            'income_years_to_replace' => 5,
            'income_inflation_adjustment' => false,
            'mortgage_balance' => 100000,
            'include_mortgage_payoff' => true,
            'education_children_count' => 0,
            'education_inflation_adjustment' => false,
            'existing_life_insurance' => 25000,
            'liquid_assets_allocated' => 10000,
        ]);

        $fna->refresh();

        $this->assertTrue($fna->dime_completed);
        $this->assertGreaterThan(0, (float) $fna->protection_gap);
    }

    public function test_wizard_page_loads_for_owner(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($user, ['client_name' => 'Wizard Page']);

        $this->actingAs($user)
            ->get(route('team.fna.wizard', $fna))
            ->assertOk()
            ->assertSee('Client Information')
            ->assertSeeLivewire(FnaWizard::class);
    }
}
