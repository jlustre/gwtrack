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
            'submit mentor feedback',
            'view own mentor feedback requests',
            'view CFM effectiveness',
            'manage CFM evaluations',
            'view CFM reports',
            'manage recognition',
            'manage mentor reviews',
            'view analytics',
            'manage action plans',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name);
        }

        $cfmSelf = [
            'view CFM effectiveness',
            'view analytics',
        ];

        $trainee = [
            'submit mentor feedback',
            'view own mentor feedback requests',
        ];

        $agencyOwner = array_merge($cfmSelf, $trainee, [
            'manage CFM evaluations',
            'view CFM reports',
            'manage recognition',
            'manage mentor reviews',
            'manage action plans',
        ]);

        $admin = $permissions;

        $roleMap = [
            'super-admin' => $admin,
            'admin' => $admin,
            'agency-owner' => $agencyOwner,
            'team-leader' => [
                'view CFM effectiveness',
                'view CFM reports',
                'view analytics',
                'manage mentor reviews',
            ],
            'certified-field-mentor' => $cfmSelf,
            'trainer' => ['view CFM effectiveness'],
            'member' => $trainee,
            'associate' => $trainee,
            'new-recruit' => $trainee,
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
