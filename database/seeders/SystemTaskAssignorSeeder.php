<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SystemTaskAssignor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SystemTaskAssignorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::withTrashed()->find(SystemTaskAssignor::USER_ID);

        if (! $user) {
            $user = new User([
                'name' => SystemTaskAssignor::NAME,
                'email' => SystemTaskAssignor::EMAIL,
                'password' => Hash::make(str()->random(64)),
                'is_active' => false,
                'is_online' => false,
                'email_verified_at' => now(),
            ]);
            $user->id = SystemTaskAssignor::USER_ID;
            $user->save();
        } else {
            $user->forceFill([
                'name' => SystemTaskAssignor::NAME,
                'email' => SystemTaskAssignor::EMAIL,
                'is_active' => false,
                'is_online' => false,
            ])->save();
        }

        Role::findOrCreate('system');
        $user->syncRoles(['system']);
    }
}