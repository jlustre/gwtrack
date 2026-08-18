<div @class(['space-y-6' => ! $embedded])>
    @unless ($embedded)
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                    <h1 class="mt-2 text-2xl font-semibold">Shared With Me</h1>
                    <p class="mt-2 text-sm text-slate-200">Prospect records collaborators have shared with you.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('team.prospects.screen', 'prospects') }}#shared-by-me" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Shared By Me</a>
                </div>
            </div>
        </div>
    @endunless

    <section @class(['overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm' => ! $embedded, 'rounded-xl border border-slate-200 bg-white shadow-sm' => $embedded]) id="shared-with-me">
        @if ($embedded)
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-gw-primary">Shared With Me</h2>
                <p class="mt-1 text-sm text-slate-600">Prospect records collaborators have shared with you.</p>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gw-accent-soft text-left text-xs font-bold uppercase tracking-wide text-gw-primary">
                    <tr>
                        <th class="px-4 py-3">Prospect</th>
                        <th class="px-4 py-3">Shared By</th>
                        <th class="px-4 py-3">Stage</th>
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
                                    {{ $share->prospect->displayName() }}
                                    <span class="ml-1 text-xs font-semibold uppercase text-slate-500">{{ $share->prospect->interest_level }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->grantedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->prospect?->stage?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->permission?->name ?? 'View Only' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->granted_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $share->expires_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($share->prospect && auth()->user()->can('view', $share->prospect))
                                    <a href="{{ route('team.prospects.records.show', $share->prospect) }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">View Profile</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No prospects have been shared with you.</td>
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
