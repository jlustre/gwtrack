<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaIndex;
use App\Models\User;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_filter_by_status_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Draft Only']);
        $approved = app(FnaRecordService::class)->create($user, ['client_name' => 'Approved Only']);
        $approved->update(['status' => 'approved_by_cfm', 'approved_at' => now()]);

        $this->actingAs($user)
            ->get(route('team.fna.index', ['status' => 'approved_by_cfm']))
            ->assertOk()
            ->assertSee('Approved Only')
            ->assertDontSee('Draft Only');

        Livewire::actingAs($user)
            ->test(FnaIndex::class)
            ->set('statusFilter', 'draft')
            ->assertSee('Draft Only')
            ->assertDontSee('Approved Only');
    }

    public function test_search_filter_matches_client_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Unique Alpha Client']);
        app(FnaRecordService::class)->create($user, ['client_name' => 'Other Beta Client']);

        Livewire::actingAs($user)
            ->test(FnaIndex::class)
            ->set('search', 'Alpha')
            ->assertSee('Unique Alpha Client')
            ->assertDontSee('Other Beta Client');
    }
}
