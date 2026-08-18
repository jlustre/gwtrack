<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin Management"
        title="Roles & Permissions"
        description="Manage Spatie roles, assign every access permission, and export the matrix back to RolePermissionSeeder."
    >
        <x-slot:actions>
            @if ($canUpdateSeeder)
                <button
                    type="button"
                    wire:click="updateSeeder"
                    wire:loading.attr="disabled"
                    wire:target="updateSeeder"
                    class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="updateSeeder">Update Seeder</span>
                    <span wire:loading wire:target="updateSeeder">Updating…</span>
                </button>
            @endif
        </x-slot:actions>
    </x-admin.page-hero>

    @if ($statusMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ $statusMessage }}
        </div>
    @endif

    @error('selectedPermissions')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror
    @error('selectedRoleId')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-tracker-stat-card label="Roles" :value="$roles->count()" theme="navy" subtitle="Including system" />
        <x-tracker-stat-card label="Permissions" :value="$totalPermissions" theme="emerald" subtitle="Access items in catalog" />
        <x-tracker-stat-card label="Implemented" :value="$implementedCount" theme="cyan" subtitle="Enforced in application code" />
        <x-tracker-stat-card
            label="Selected role grants"
            :value="count($selectedPermissions)"
            theme="gold"
            :subtitle="$selectedRole?->name ?? 'None selected'"
        />
    </div>

    <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-4">
            <section class="overflow-hidden rounded-xl border-2 border-gw-primary/15 bg-white shadow-md">
                <div class="border-b border-slate-100 bg-gw-primary-soft/70 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gw-primary">Roles</h2>
                </div>
                <div class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($roles as $role)
                        <button
                            type="button"
                            wire:click="selectRole({{ $role->id }})"
                            @class([
                                'flex w-full items-start justify-between gap-2 px-4 py-3 text-left transition',
                                'bg-gw-accent-muted' => $selectedRoleId === $role->id,
                                'hover:bg-gw-accent-soft' => $selectedRoleId !== $role->id,
                            ])
                        >
                            <div>
                                <p class="text-sm font-semibold text-gw-primary">{{ $role->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $role->permissions_count }} perms · {{ $role->users_count }} users
                                </p>
                            </div>
                            @if ($role->name === 'system')
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">Locked</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gw-primary">Create role</h3>
                <p class="mt-1 text-xs text-slate-500">Kebab-case slug only.</p>
                <div class="mt-3 flex gap-2">
                    <input
                        type="text"
                        wire:model="newRoleName"
                        placeholder="regional-leader"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                    <button type="button" wire:click="createRole" class="shrink-0 rounded-md bg-gw-primary px-3 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                        Add
                    </button>
                </div>
                @error('newRoleName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gw-primary">Create permission</h3>
                <p class="mt-1 text-xs text-slate-500">Lowercase access item name.</p>
                <div class="mt-3 flex gap-2">
                    <input
                        type="text"
                        wire:model="newPermissionName"
                        placeholder="manage reports"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                    <button type="button" wire:click="createPermission" class="shrink-0 rounded-md bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Add
                    </button>
                </div>
                @error('newPermissionName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </section>
        </aside>

        <section class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Editing role</p>
                        <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $selectedRole?->name ?? 'Select a role' }}</h2>
                        @if ($isLocked)
                            <p class="mt-1 text-sm text-amber-700">System role is locked and always has zero permissions.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Filter permissions…"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                        <button type="button" wire:click="selectAllVisible" @disabled($isLocked) class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                            Select visible
                        </button>
                        <button type="button" wire:click="clearAllVisible" @disabled($isLocked) class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                            Clear visible
                        </button>
                        <button
                            type="button"
                            wire:click="saveRolePermissions"
                            @disabled($isLocked || ! $selectedRole)
                            class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:opacity-50"
                        >
                            Save permissions
                        </button>
                        @if ($selectedRole && ! in_array($selectedRole->name, ['system', 'super-admin', 'member'], true))
                            <button
                                type="button"
                                wire:click="deleteRole"
                                wire:confirm="Delete role {{ $selectedRole->name }}? This cannot be undone."
                                class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"
                            >
                                Delete role
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @forelse ($groups as $groupKey => $permissions)
                @php
                    $meta = $groupMeta[$groupKey] ?? ['label' => str($groupKey)->title(), 'description' => ''];
                    $isOpen = $openGroupKey === $groupKey;
                    $grantedInGroup = $permissions->filter(fn ($permission) => in_array($permission->name, $selectedPermissions, true))->count();
                @endphp
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div @class([
                        'flex flex-col gap-3 bg-gradient-to-r from-gw-accent-soft via-white to-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between',
                        'border-b border-slate-100' => $isOpen,
                    ])>
                        <button
                            type="button"
                            wire:click="toggleGroupPanel(@js($groupKey))"
                            class="flex min-w-0 flex-1 items-start gap-3 text-left"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        >
                            <span @class([
                                'mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border text-sm font-bold transition',
                                'border-gw-accent bg-gw-accent text-gw-primary' => $isOpen,
                                'border-slate-300 bg-white text-slate-500' => ! $isOpen,
                            ])>
                                {{ $isOpen ? '−' : '+' }}
                            </span>
                            <span class="min-w-0">
                                <span class="block text-base font-semibold text-gw-primary">{{ $meta['label'] }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">
                                    {{ $meta['description'] }} · {{ $permissions->count() }} items · {{ $grantedInGroup }} granted
                                    · {{ $isOpen ? 'Hide' : 'Show' }}
                                </span>
                            </span>
                        </button>
                        <div class="flex gap-2 sm:shrink-0">
                            <button type="button" wire:click="selectGroup('{{ $groupKey }}')" @disabled($isLocked) class="rounded-md border border-gw-accent/40 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary disabled:opacity-50">
                                Select group
                            </button>
                            <button type="button" wire:click="clearGroup('{{ $groupKey }}')" @disabled($isLocked) class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-50">
                                Clear group
                            </button>
                        </div>
                    </div>

                    @if ($isOpen)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-gw-primary-soft text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 w-12">Grant</th>
                                        <th class="px-4 py-3">Permission</th>
                                        <th class="px-4 py-3">Access status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $checked = in_array($permission->name, $selectedPermissions, true);
                                            $isImplemented = in_array($permission->name, $implemented, true);
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    wire:click="togglePermission(@js($permission->name))"
                                                    @checked($checked)
                                                    @disabled($isLocked)
                                                    class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent disabled:opacity-50"
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="font-semibold text-gw-primary">{{ $permission->name }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($isImplemented)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Implemented</span>
                                                @else
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Catalog only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
                    No permissions match your filter.
                </div>
            @endforelse
        </section>
    </div>
</div>
