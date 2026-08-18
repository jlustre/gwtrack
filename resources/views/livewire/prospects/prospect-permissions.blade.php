<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Access Manager</h1>
                <p class="mt-2 text-sm text-slate-200">Grant, expire, revoke, and audit prospect sharing permissions.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
                <a href="{{ route('team.prospects.screen', 'prospects') }}#shared-by-me" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Shared By Me</a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gw-primary/15 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-950 px-6 py-4 text-white md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Phase 8 Governance</p>
                <h2 class="mt-1 text-lg font-semibold">CRM Governance & Calendar Risk Review</h2>
            </div>
            <p class="text-sm text-slate-300">Shared access, recent views, and linked CRM calendar visibility.</p>
        </div>

        <div class="grid gap-px bg-slate-200 md:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                'Owned prospects' => $governanceReport['metrics']['owned_prospects'],
                'Active shares' => $governanceReport['metrics']['active_shares'],
                'Expiring soon' => $governanceReport['metrics']['expiring_shares'],
                'Open ended' => $governanceReport['metrics']['stale_open_shares'],
                'External views' => $governanceReport['metrics']['external_views'],
                'Calendar reviews' => $governanceReport['metrics']['calendar_exposure'],
            ] as $label => $value)
                <div class="bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold text-gw-primary">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 p-4 xl:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gw-primary">Review Queue</h3>
                <div class="mt-3 space-y-3">
                    @foreach ($governanceReport['review_items'] as $item)
                        @php
                            $badgeClasses = [
                                'attention' => 'border-amber-200 bg-amber-50 text-amber-800',
                                'warning' => 'border-red-200 bg-red-50 text-red-700',
                                'info' => 'border-sky-200 bg-sky-50 text-sky-700',
                                'clear' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            ][$item['severity']] ?? 'border-slate-200 bg-slate-100 text-slate-700';
                        @endphp
                        <div class="rounded-lg border px-3 py-2 {{ $badgeClasses }}">
                            <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                            <p class="mt-1 text-xs">{{ $item['detail'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gw-primary">Expiring Access</h3>
                <div class="mt-3 space-y-3">
                    @forelse ($governanceReport['expiring_shares'] as $share)
                        @php
                            $prospectName = trim(($share->preferred_name ?: trim($share->first_name.' '.$share->last_name)) ?: 'Unnamed Prospect');
                        @endphp
                        <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <p class="text-sm font-semibold text-gw-primary">{{ $prospectName }}</p>
                            <p class="text-xs text-slate-500">{{ $share->collaborator_name }} - expires {{ now()->parse($share->expires_at)->format('M j, Y') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No shared access expires in the next 7 days.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gw-primary">Calendar Exposure</h3>
                <div class="mt-3 space-y-3">
                    @forelse ($governanceReport['calendar_exposures'] as $event)
                        @php
                            $prospectName = trim(($event->preferred_name ?: trim($event->first_name.' '.$event->last_name)) ?: 'Unnamed Prospect');
                        @endphp
                        <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <p class="text-sm font-semibold text-gw-primary">{{ $event->title }}</p>
                            <p class="text-xs text-slate-500">{{ $prospectName }} - {{ str($event->visibility)->title() }} - {{ now()->parse($event->starts_at)->format('M j, Y g:i A') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No upcoming CRM calendar events need visibility review.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gw-accent/30 bg-white p-4 shadow-sm">
        <h2 class="text-base font-semibold text-gw-primary">Bulk Visibility Preset</h2>
        <p class="mt-1 text-sm text-slate-600">Apply a visibility preset to one of your prospects.</p>
        <form wire:submit="applyBulkPreset" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <label class="block text-sm font-semibold text-gw-primary">
                Prospect
                <select wire:model="bulkProspectId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Select prospect…</option>
                    @foreach ($ownedProspects as $prospect)
                        <option value="{{ $prospect->id }}">{{ $prospect->displayName() }} ({{ $visibilityPresets[$prospect->visibility_preset] ?? $prospect->visibility_preset }})</option>
                    @endforeach
                </select>
                @error('bulkProspectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Preset
                <select wire:model="bulkPreset" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($visibilityPresets as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Permission
                <select wire:model="bulkPermissionId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">View Only (default)</option>
                    @foreach ($permissions as $permission)
                        <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Expires
                <input type="date" wire:model="bulkExpiresAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </label>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Apply Preset</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gw-primary">Shares Granted By You</h2>
            <select wire:model.live="shareStatusFilter" class="rounded-lg border-slate-300 text-sm">
                <option value="active">Active</option>
                <option value="inactive">Expired / Revoked</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gw-accent-soft text-left text-xs font-bold uppercase tracking-wide text-gw-primary">
                    <tr>
                        <th class="px-4 py-3">Prospect</th>
                        <th class="px-4 py-3">Collaborator</th>
                        <th class="px-4 py-3">Permission</th>
                        <th class="px-4 py-3">Granted</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($shares as $share)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gw-primary">
                                @if ($share->prospect)
                                    <a href="{{ route('team.prospects.records.show', $share->prospect) }}" class="hover:text-gw-accent-dark">{{ $share->prospect->displayName() }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->sharedWith?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->permission?->name ?? 'View Only' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->granted_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->expires_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($share->isActive())
                                    <button type="button" wire:click="revokeShare({{ $share->id }})" wire:confirm="Revoke this share?" class="text-sm font-semibold text-red-600 hover:text-red-800">Revoke</button>
                                @else
                                    <span class="text-xs font-semibold uppercase text-slate-400">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No shares match this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shares->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $shares->links() }}</div>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gw-primary">Access Log</h2>
            <p class="mt-1 text-sm text-slate-600">Activity on prospects you own or shares you granted.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gw-accent-soft text-left text-xs font-bold uppercase tracking-wide text-gw-primary">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Prospect</th>
                        <th class="px-4 py-3">Actor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($accessLogs as $log)
                        <tr>
                            <td class="px-4 py-3 text-slate-600">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3 font-medium text-gw-primary">{{ str($log->action)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($log->prospect)
                                    {{ $log->prospect->first_name }} {{ $log->prospect->last_name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->actor?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No access log entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accessLogs->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $accessLogs->links() }}</div>
        @endif
    </div>
</div>
