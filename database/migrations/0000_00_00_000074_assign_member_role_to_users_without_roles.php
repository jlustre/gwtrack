<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $memberRole = Role::query()->where('name', 'member')->first();

        if (! $memberRole) {
            return;
        }

        $assignedRoleIds = User::query()
            ->where('is_active', true)
            ->whereDoesntHave('roles')
            ->pluck('id');

        foreach ($assignedRoleIds as $userId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $memberRole->id,
                'model_type' => User::class,
                'model_id' => $userId,
            ]);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
