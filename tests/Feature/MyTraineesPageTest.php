<?php

namespace Tests\Feature;

use App\Livewire\Team\MyTrainees;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyTraineesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cfm_can_open_my_trainees_roster(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('team.trainees'))
            ->assertOk()
            ->assertSee('My Trainees', false)
            ->assertSee('Trainee roster', false)
            ->assertSee('Coaching priorities', false)
            ->assertSee('Maya Chen', false);

        Livewire::actingAs($cfm)
            ->test(MyTrainees::class)
            ->set('statusFilter', 'at_risk')
            ->assertSet('statusFilter', 'at_risk')
            ->call('setViewMode', 'cards')
            ->assertSet('viewMode', 'cards')
            ->call('clearFilters')
            ->assertSet('statusFilter', 'all')
            ->assertSee('Maya Chen', false);
    }

    public function test_regular_member_cannot_open_my_trainees(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('team.trainees'))
            ->assertForbidden();
    }
}
