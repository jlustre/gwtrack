<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view announcements',
            'create announcements',
            'edit announcements',
            'delete announcements',
            'publish announcements',
            'manage recognition posts',
            'manage campaigns',
            'manage newsletters',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name);
        }

        $viewOnly = ['view announcements'];

        $author = [
            'view announcements',
            'create announcements',
            'edit announcements',
            'delete announcements',
            'publish announcements',
            'manage recognition posts',
            'manage campaigns',
            'manage newsletters',
        ];

        $roleMap = [
            'super-admin' => $author,
            'admin' => $author,
            'agency-owner' => $author,
            'team-leader' => $author,
            'certified-field-mentor' => $viewOnly,
            'trainer' => $viewOnly,
            'member' => $viewOnly,
            'associate' => $viewOnly,
            'new-recruit' => $viewOnly,
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
