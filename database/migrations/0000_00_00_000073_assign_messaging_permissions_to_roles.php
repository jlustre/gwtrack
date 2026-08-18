<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $all = [
            'send messages',
            'view conversations',
            'delete own messages',
            'delete group messages',
            'manage message groups',
            'send message broadcasts',
            'view communication analytics',
            'archive conversations',
            'restore conversations',
        ];

        foreach ($all as $name) {
            Permission::findOrCreate($name);
        }

        $basic = [
            'send messages',
            'view conversations',
            'delete own messages',
            'archive conversations',
            'restore conversations',
        ];

        $mentor = array_merge($basic, [
            'delete group messages',
            'manage message groups',
        ]);

        $leader = array_merge($mentor, [
            'view communication analytics',
        ]);

        $executive = array_merge($leader, [
            'send message broadcasts',
        ]);

        $roleMap = [
            'member' => $basic,
            'associate' => $basic,
            'new-recruit' => $basic,
            'trainer' => $basic,
            'certified-field-mentor' => $mentor,
            'team-leader' => $leader,
            'agency-owner' => $executive,
            'admin' => $all,
            'super-admin' => $all,
            'support-agent' => $basic,
        ];

        foreach ($roleMap as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
