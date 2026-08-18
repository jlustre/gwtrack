<x-app-layout>
    <div class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="User Management"
            description="Manage member role, rank, team, sponsor, status, and lifecycle access."
        >
            <x-slot:actions>
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                    Add User
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        @if (session('status'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ str(session('status'))->replace('-', ' ')->title() }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="search" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Name or email">
            </div>

            <div>
                <label for="role" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</label>
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="rank_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</label>
                <select id="rank_id" name="rank_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All ranks</option>
                    @foreach ($ranks as $rank)
                        <option value="{{ $rank->id }}" @selected((string) ($filters['rank_id'] ?? '') === (string) $rank->id)>{{ $rank->code }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="team_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Team</label>
                <select id="team_id" name="team_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All teams</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) ($filters['team_id'] ?? '') === (string) $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Any</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1')>Active</option>
                    <option value="0" @selected(($filters['status'] ?? '') === '0')>Inactive</option>
                </select>
            </div>

            <div>
                <label for="trashed" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Archived</label>
                <select id="trashed" name="trashed" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Active only</option>
                    <option value="with" @selected(($filters['trashed'] ?? '') === 'with')>Include archived</option>
                    <option value="only" @selected(($filters['trashed'] ?? '') === 'only')>Archived only</option>
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-5">
                <button type="submit" class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-[#d6b45f]">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">Team</th>
                            <th class="px-4 py-3">Sponsor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Login</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="{{ $user->trashed() ? 'bg-slate-50 text-slate-500' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gw-primary">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $rolePriority = ['system', 'super-admin', 'admin', 'agency-owner', 'team-leader', 'certified-field-mentor', 'trainer', 'support-agent', 'associate', 'member', 'new-recruit'];
                                        $roleNames = $user->getRoleNames();
                                        $displayRole = collect($rolePriority)->first(fn ($role) => $roleNames->contains($role)) ?? $roleNames->first();
                                    @endphp
                                    {{ $displayRole ?? 'none' }}
                                </td>
                                <td class="px-4 py-3">{{ $user->rank?->code ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $user->team?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $user->sponsor?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $user->trashed() ? 'bg-slate-200 text-slate-600' : ($user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $user->trashed() ? 'Archived' : ($user->is_active ? 'Active' : 'Inactive') }}
                                    </span>
                                    @if ($user->is_online && ! $user->trashed())
                                        <span class="ml-1 rounded-full bg-gw-primary px-2 py-1 text-xs font-semibold text-white">Online</span>
                                    @endif
                                    @if ($user->isMessagingSuspended() && ! $user->trashed())
                                        <span class="ml-1 rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Messaging suspended</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm font-semibold text-gw-primary hover:text-gw-accent">Manage</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">No users match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
