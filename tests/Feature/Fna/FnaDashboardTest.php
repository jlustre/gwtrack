<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaDashboard;
use App\Models\User;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_loads_with_livewire_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Widget Client']);

        $this->actingAs($user)
            ->get(route('team.fna.dashboard'))
            ->assertOk()
            ->assertSee('FNA Management')
            ->assertSee('Total FNAs')
            ->assertSee('Awaiting CFM Review')
            ->assertSee('Status Breakdown');

        Livewire::actingAs($user)
            ->test(FnaDashboard::class)
            ->assertSee('Total FNAs')
            ->assertSee('Draft FNAs')
            ->assertSee('View All FNAs');
    }
}
