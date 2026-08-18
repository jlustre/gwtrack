<?php

namespace Tests\Feature;

use App\Livewire\Admin\Roles\RolesPermissionsManager;
use App\Services\Roles\RolePermissionSeederExporter;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermissionsManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_super_admin_can_open_roles_permissions_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSeeText('Roles & Permissions')
            ->assertSeeText('Update Seeder');
    }

    public function test_admin_without_manage_roles_is_forbidden(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_can_save_role_permissions(): void
    {
        $role = Role::findByName('trainer');

        Livewire::actingAs($this->superAdmin)
            ->test(RolesPermissionsManager::class)
            ->call('selectRole', $role->id)
            ->set('selectedPermissions', ['view dashboard', 'manage training', 'view training summary'])
            ->call('saveRolePermissions')
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('manage training'));
        $this->assertFalse($role->hasPermissionTo('manage prospects'));
    }

    public function test_can_create_permission_and_role(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(RolesPermissionsManager::class)
            ->set('newPermissionName', 'manage specialty reports')
            ->call('createPermission')
            ->assertHasNoErrors()
            ->set('newRoleName', 'regional-leader')
            ->call('createRole')
            ->assertHasNoErrors();

        $this->assertNotNull(Permission::findByName('manage specialty reports'));
        $this->assertNotNull(Role::findByName('regional-leader'));
    }

    public function test_update_seeder_writes_role_permission_seeder(): void
    {
        $path = app(RolePermissionSeederExporter::class)->path();
        $original = file_get_contents($path);

        try {
            Livewire::actingAs($this->superAdmin)
                ->test(RolesPermissionsManager::class)
                ->call('updateSeeder')
                ->assertHasNoErrors()
                ->assertSet('statusMessage', 'RolePermissionSeeder updated from the current matrix.');

            $contents = file_get_contents($path);
            $this->assertStringContainsString('Regenerated from Admin → Roles & Permissions → Update Seeder', $contents);
            $this->assertStringContainsString("'manage roles'", $contents);
            $this->assertStringContainsString("'super-admin'", $contents);
        } finally {
            file_put_contents($path, $original);
        }
    }
}
