<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingAcademyDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_training_academy_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('training.index'))
            ->assertOk()
            ->assertSee('Training Center')
            ->assertSee('GWTrack Academy')
            ->assertSee('Learning Paths')
            ->assertSee('New Associate Path')
            ->assertSee('Prospecting Fundamentals');
    }
}
