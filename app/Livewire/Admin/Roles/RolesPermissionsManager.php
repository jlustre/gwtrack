<?php

namespace App\Livewire\Admin\Roles;

use App\Services\Roles\RolePermissionSeederExporter;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsManager extends Component
{
    public ?int $selectedRoleId = null;

    public string $search = '';

    public ?string $openGroupKey = null;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public string $newRoleName = '';

    public string $newPermissionName = '';

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);

        $this->selectedRoleId = Role::query()
            ->where('name', '!=', 'system')
            ->orderBy('name')
            ->value('id');

        $this->loadSelectedRolePermissions();
        $this->openFirstVisibleGroup();
    }

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->statusMessage = '';
        $this->loadSelectedRolePermissions();
    }

    public function updatedSearch(): void
    {
        // Keep accordion to a single visible group after filtering.
        if ($this->openGroupKey && ! PermissionCatalog::groupedPermissions($this->search)->has($this->openGroupKey)) {
            $this->openFirstVisibleGroup();
        }
    }

    public function toggleGroupPanel(string $groupKey): void
    {
        $this->openGroupKey = $this->openGroupKey === $groupKey ? null : $groupKey;
    }

    public function togglePermission(string $permission): void
    {
        if ($this->isRoleLocked()) {
            return;
        }

        if (in_array($permission, $this->selectedPermissions, true)) {
            $this->selectedPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn (string $name) => $name !== $permission
            ));

            return;
        }

        $this->selectedPermissions[] = $permission;
        $this->selectedPermissions = array_values(array_unique($this->selectedPermissions));
    }

    public function selectGroup(string $groupKey): void
    {
        if ($this->isRoleLocked()) {
            return;
        }

        $names = PermissionCatalog::groupedPermissions($this->search)
            ->get($groupKey, collect())
            ->pluck('name')
            ->all();

        $this->selectedPermissions = array_values(array_unique([
            ...$this->selectedPermissions,
            ...$names,
        ]));
    }

    public function clearGroup(string $groupKey): void
    {
        if ($this->isRoleLocked()) {
            return;
        }

        $names = PermissionCatalog::groupedPermissions($this->search)
            ->get($groupKey, collect())
            ->pluck('name')
            ->all();

        $this->selectedPermissions = array_values(array_filter(
            $this->selectedPermissions,
            fn (string $name) => ! in_array($name, $names, true)
        ));
    }

    public function selectAllVisible(): void
    {
        if ($this->isRoleLocked()) {
            return;
        }

        $names = PermissionCatalog::groupedPermissions($this->search)
            ->flatten(1)
            ->pluck('name')
            ->all();

        $this->selectedPermissions = array_values(array_unique([
            ...$this->selectedPermissions,
            ...$names,
        ]));
    }

    public function clearAllVisible(): void
    {
        if ($this->isRoleLocked()) {
            return;
        }

        $names = PermissionCatalog::groupedPermissions($this->search)
            ->flatten(1)
            ->pluck('name')
            ->all();

        $this->selectedPermissions = array_values(array_filter(
            $this->selectedPermissions,
            fn (string $name) => ! in_array($name, $names, true)
        ));
    }

    public function saveRolePermissions(): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);

        $role = $this->selectedRole();
        abort_unless($role, 404);
        abort_if($role->name === 'system', 403);

        if ($role->name === 'super-admin' && ! in_array('manage roles', $this->selectedPermissions, true)) {
            $this->addError('selectedPermissions', 'Super Admin must keep the manage roles permission.');

            return;
        }

        if (
            auth()->user()?->hasRole($role->name)
            && $role->name !== 'super-admin'
            && ! in_array('manage roles', $this->selectedPermissions, true)
            && $this->usersWithManageRolesCount() <= 1
        ) {
            $this->addError('selectedPermissions', 'Cannot remove manage roles from the last privileged administrator.');

            return;
        }

        $valid = Permission::query()
            ->whereIn('name', $this->selectedPermissions)
            ->pluck('name')
            ->all();

        $role->syncPermissions($valid);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->statusMessage = 'Permissions saved for '.$role->name.'.';
        $this->loadSelectedRolePermissions();
    }

    public function createRole(): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);

        $validated = $this->validate([
            'newRoleName' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name'),
            ],
        ], [
            'newRoleName.regex' => 'Use a kebab-case slug (e.g. regional-leader).',
        ]);

        $role = Role::findOrCreate($validated['newRoleName']);
        $role->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newRoleName = '';
        $this->selectedRoleId = $role->id;
        $this->loadSelectedRolePermissions();
        $this->statusMessage = 'Role '.$role->name.' created.';
    }

    public function createPermission(): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);

        $validated = $this->validate([
            'newPermissionName' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:[ -][a-z0-9]+)*$/',
                Rule::unique('permissions', 'name'),
            ],
        ], [
            'newPermissionName.regex' => 'Use lowercase words (e.g. manage reports).',
        ]);

        $name = Str::of($validated['newPermissionName'])->lower()->replace('_', ' ')->squish()->toString();
        Permission::findOrCreate($name);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newPermissionName = '';
        $this->statusMessage = 'Permission "'.$name.'" created. Assign it to roles below.';
    }

    public function deleteRole(): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);

        $role = $this->selectedRole();
        abort_unless($role, 404);
        abort_if(in_array($role->name, ['system', 'super-admin', 'member'], true), 403);

        $assignedUsers = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($assignedUsers > 0) {
            $this->addError('selectedRoleId', "Cannot delete {$role->name}: {$assignedUsers} user(s) still assigned.");

            return;
        }

        $name = $role->name;
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->selectedRoleId = Role::query()->where('name', '!=', 'system')->orderBy('name')->value('id');
        $this->loadSelectedRolePermissions();
        $this->statusMessage = 'Role '.$name.' deleted.';
    }

    public function updateSeeder(RolePermissionSeederExporter $exporter): void
    {
        abort_unless(auth()->user()?->can('manage roles'), 403);
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $exporter->export();
        $this->statusMessage = 'RolePermissionSeeder updated from the current matrix.';
    }

    public function render(): View
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->withCount('users')
            ->orderByRaw("CASE WHEN name = 'system' THEN 1 ELSE 0 END")
            ->orderBy('name')
            ->get();

        $selectedRole = $this->selectedRole();
        $groups = PermissionCatalog::groupedPermissions($this->search);
        $groupMeta = PermissionCatalog::groups();
        $implemented = PermissionCatalog::implementedPermissions();

        $totalPermissions = Permission::query()->count();
        $implementedCount = Permission::query()->whereIn('name', $implemented)->count();

        return view('livewire.admin.roles.roles-permissions-manager', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'groups' => $groups,
            'groupMeta' => $groupMeta,
            'implemented' => $implemented,
            'totalPermissions' => $totalPermissions,
            'implementedCount' => $implementedCount,
            'isLocked' => $this->isRoleLocked(),
            'canUpdateSeeder' => auth()->user()?->hasRole('super-admin') ?? false,
        ])->layout('layouts.app');
    }

    private function loadSelectedRolePermissions(): void
    {
        $role = $this->selectedRole();

        $this->selectedPermissions = $role
            ? $role->permissions()->pluck('name')->values()->all()
            : [];
    }

    private function selectedRole(): ?Role
    {
        if (! $this->selectedRoleId) {
            return null;
        }

        return Role::query()->with('permissions')->find($this->selectedRoleId);
    }

    private function isRoleLocked(): bool
    {
        return $this->selectedRole()?->name === 'system';
    }

    private function usersWithManageRolesCount(): int
    {
        return Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'manage roles'))
            ->withCount('users')
            ->get()
            ->sum('users_count');
    }

    private function openFirstVisibleGroup(): void
    {
        $this->openGroupKey = PermissionCatalog::groupedPermissions($this->search)->keys()->first();
    }
}
