<?php

namespace App\Services\Roles;

use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeederExporter
{
    public function path(): string
    {
        return database_path('seeders/RolePermissionSeeder.php');
    }

    public function export(): string
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Role $role) {
                return [
                    $role->name => $role->permissions
                        ->pluck('name')
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        $permissionsExport = $this->exportPhpArray($permissions, 2);
        $rolesExport = $this->exportPhpArray($roles, 2);

        $contents = '<?php'.PHP_EOL.PHP_EOL;
        $contents .= 'namespace Database\\Seeders;'.PHP_EOL.PHP_EOL;
        $contents .= 'use Illuminate\\Database\\Seeder;'.PHP_EOL;
        $contents .= 'use Illuminate\\Support\\Facades\\DB;'.PHP_EOL;
        $contents .= 'use Spatie\\Permission\\Models\\Permission;'.PHP_EOL;
        $contents .= 'use Spatie\\Permission\\Models\\Role;'.PHP_EOL.PHP_EOL;
        $contents .= '/**'.PHP_EOL;
        $contents .= ' * Regenerated from Admin → Roles & Permissions → Update Seeder.'.PHP_EOL;
        $contents .= ' * Source of truth after export is the current database role/permission matrix.'.PHP_EOL;
        $contents .= ' */'.PHP_EOL;
        $contents .= 'class RolePermissionSeeder extends Seeder'.PHP_EOL;
        $contents .= '{'.PHP_EOL;
        $contents .= '    public function run(): void'.PHP_EOL;
        $contents .= '    {'.PHP_EOL;
        $contents .= '        app()[\\Spatie\\Permission\\PermissionRegistrar::class]->forgetCachedPermissions();'.PHP_EOL.PHP_EOL;
        $contents .= '        $permissions = '.$permissionsExport.';'.PHP_EOL.PHP_EOL;
        $contents .= '        foreach ($permissions as $permission) {'.PHP_EOL;
        $contents .= '            Permission::findOrCreate($permission);'.PHP_EOL;
        $contents .= '        }'.PHP_EOL.PHP_EOL;
        $contents .= $this->legacyRoleMigrationBlock();
        $contents .= '        $roles = '.$rolesExport.';'.PHP_EOL.PHP_EOL;
        $contents .= '        foreach ($roles as $roleName => $rolePermissions) {'.PHP_EOL;
        $contents .= '            Role::findOrCreate($roleName)->syncPermissions($rolePermissions);'.PHP_EOL;
        $contents .= '        }'.PHP_EOL;
        $contents .= '    }'.PHP_EOL;
        $contents .= '}'.PHP_EOL;

        File::put($this->path(), $contents);

        return $this->path();
    }

    private function legacyRoleMigrationBlock(): string
    {
        return <<<'PHP'
        $legacyRoleNames = [
            'Super Admin' => 'super-admin',
            'Agency Owner' => 'agency-owner',
            'Team Leader' => 'team-leader',
            'Certified Field Mentor' => 'certified-field-mentor',
            'Trainer' => 'trainer',
            'Admin' => 'admin',
            'Member' => 'member',
            'Associate' => 'associate',
            'New Recruit' => 'new-recruit',
        ];

        foreach ($legacyRoleNames as $legacyName => $slugName) {
            $legacyRole = Role::where('name', $legacyName)->first();
            $slugRole = Role::where('name', $slugName)->first();

            if ($legacyRole && ! $slugRole) {
                $legacyRole->forceFill(['name' => $slugName])->save();
            } elseif ($legacyRole && $slugRole) {
                DB::table('model_has_roles')
                    ->where('role_id', $legacyRole->id)
                    ->update(['role_id' => $slugRole->id]);

                DB::table('role_has_permissions')
                    ->where('role_id', $legacyRole->id)
                    ->delete();

                $legacyRole->delete();
            }
        }


PHP;
    }

    private function exportPhpArray(mixed $value, int $indent = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $isList = array_is_list($value);
        $pad = str_repeat('    ', $indent);
        $innerPad = str_repeat('    ', $indent + 1);
        $lines = ['['];

        foreach ($value as $key => $item) {
            $exportedItem = $this->exportPhpArray($item, $indent + 1);

            if ($isList) {
                $lines[] = $innerPad.$exportedItem.',';

                continue;
            }

            $lines[] = $innerPad.var_export($key, true).' => '.$exportedItem.',';
        }

        $lines[] = $pad.']';

        return implode(PHP_EOL, $lines);
    }
}
