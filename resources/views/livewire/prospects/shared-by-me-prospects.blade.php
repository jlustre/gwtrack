<div @class(['space-y-6' => ! $embedded])>
    @unless ($embedded)
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                    <h1 class="mt-2 text-2xl font-semibold">Shared By Me</h1>
                    <p class="mt-2 text-sm text-slate-200">Manage prospect records you have shared with collaborators.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('team.prospects.access-manager') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Access Manager</a>
                </div>
            </div>
        </div>
    @endunless

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <section @class(['overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm' => ! $embedded, 'rounded-xl border border-slate-200 bg-white shadow-sm' => $embedded]) id="shared-by-me">
        <div @class(['flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4' => ! $embedded, 'flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4' => $embedded])>
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">{{ $embedded ? 'Shared By Me' : 'Your Shares' }}</h2>
                @if ($embedded)
                    <p class="mt-1 text-sm text-slate-600">Manage prospect records you have shared with collaborators.</p>
                @endif
            </div>
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
                        <th class="px-4 py-3">Preset</th>
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
                            <td class="px-4 py-3 text-slate-600">{{ config('prospects.visibility_presets')[$share->prospect?->visibility_preset] ?? $share->prospect?->visibility_preset ?? '—' }}</td>
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
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No shares match this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shares->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $shares->links() }}</div>
        @endif
    </section>
</div>
