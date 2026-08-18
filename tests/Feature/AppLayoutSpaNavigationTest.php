<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLayoutSpaNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_shell_uses_livewire_persist_and_navigate(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-persist="sidebar"', $html);
        $this->assertStringContainsString('x-persist="topbar"', $html);
        $this->assertStringContainsString('x-persist="shell-overlays"', $html);
        $this->assertStringContainsString('id="gw-main"', $html);
        $this->assertStringContainsString('id="gw-sidebar-navigation"', $html);
        $this->assertStringContainsString('wire:navigate.hover', $html);
        $this->assertStringContainsString('livewire', strtolower($html));
    }
}
