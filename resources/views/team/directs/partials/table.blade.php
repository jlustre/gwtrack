@props(['members'])

<div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Direct Members</h2>
            <p class="text-sm text-slate-600">
                @if ($members->total() === 0)
                    No directs match your search or filters.
                @elseif ($members->total() === 1)
                    1 direct member
                @else
                    {{ $members->total() }} direct members
                @endif
                @if ($members->hasPages())
                    <span class="text-slate-400">· page {{ $members->currentPage() }} of {{ $members->lastPage() }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white/90">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="sticky top-0 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Member</th>
                    <th class="px-4 py-3">Rank</th>
                    <th class="px-4 py-3">CFM</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3">Onboarding</th>
                    <th class="px-4 py-3">Training</th>
                    <th class="px-4 py-3">Their Directs</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($members as $member)
                    @php
                        $hierarchy = app(App\Services\DownlineHierarchyService::class);
                        $progress = $hierarchy->progressSummary($member);
                    @endphp
                    <tr class="transition hover:bg-gw-accent-soft/60">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gw-primary text-xs font-bold text-white">
                                    @if ($member->profilePhotoUrl())
                                        <img src="{{ $member->profilePhotoUrl() }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        {{ $member->initials() }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('team.member.profile', $member) }}" class="truncate font-semibold text-gw-primary hover:text-gw-accent-dark">
                                        {{ $member->name }}
                                    </a>
                                    <div class="truncate text-xs text-slate-500">{{ $member->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">@include('team.downline.partials.member-badge', ['member' => $member])</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->mentor?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            <div>{{ $member->profile?->country ?? 'Global' }}</div>
                            @if ($member->profile?->city)
                                <div class="text-xs text-slate-400">{{ $member->profile->city }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->joined_at?->format('M j, Y') ?? 'Not set' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ \App\Support\ChecklistProgressDisplay::label($progress['onboarding']) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $progress['training'] }}%</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->direct_recruits_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @include('team.directs.partials.row-actions', ['member' => $member])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <p class="text-sm font-semibold text-gw-primary">No direct members found</p>
                            <p class="mt-1 text-sm text-slate-500">Try adjusting your search or filters, or recruit your first direct member.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($members->hasPages())
        <div class="mt-5 border-t border-slate-200 pt-4">
            {{ $members->links() }}
        </div>
    @endif
</div>
