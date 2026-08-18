<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_resources_hub_displays_library_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        PortalResource::query()->create([
            'created_by' => $user->id,
            'title' => 'Associate Welcome Packet',
            'description' => 'Getting started guide.',
            'type' => 'document',
            'category' => 'onboarding',
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        PortalResource::query()->create([
            'created_by' => $user->id,
            'title' => 'Team Zoom Room',
            'description' => 'Daily huddle link.',
            'type' => 'link',
            'category' => 'zoom',
            'url' => 'https://zoom.example.com/team',
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('resources.index'))
            ->assertOk()
            ->assertSee('Your field development toolkit')
            ->assertSee('Browse the library')
            ->assertSee('Associate Welcome Packet')
            ->assertSee('Team Zoom Room')
            ->assertSee('My favorites')
            ->assertSee('Quick navigation');
    }
}
