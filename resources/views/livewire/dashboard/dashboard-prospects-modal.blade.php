<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="dashboard-prospects-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div @class([
            'relative z-10 flex w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl',
            'h-[92vh]' => $showQuickAdd,
            'max-h-[88vh]' => ! $showQuickAdd,
        ])>
            <div class="border-b border-[#0E7490]/40 bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0E7490] px-5 py-3 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Prospects</p>
                        <h3 id="dashboard-prospects-modal-title" class="mt-0.5 text-base font-semibold">Your prospect directory</h3>
                        <p class="mt-0.5 text-xs text-cyan-50/85">
                            {{ $prospects->total() }} prospect{{ $prospects->total() === 1 ? '' : 's' }} · search, view, edit, or quick add from here.
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @can('create', \App\Models\Prospect::class)
                            <button
                                type="button"
                                wire:click="toggleQuickAdd"
                                class="rounded-md border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"
                            >
                                {{ $showQuickAdd ? 'Hide quick add' : 'Quick Add' }}
                            </button>
                        @endcan
                        <a
                            href="{{ $summaryUrl }}"
                            wire:click="close"
                            class="inline-flex items-center gap-1 rounded-md border border-gw-accent/60 bg-gw-accent/20 px-3 py-1.5 text-xs font-bold text-gw-accent-soft transition hover:bg-gw-accent/30"
                        >
                            Prospect Summary
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                    </div>
                </div>
            </div>

            @if ($statusMessage)
                <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-2 text-xs font-semibold text-emerald-800">
                    {{ $statusMessage }}
                </div>
            @endif

            @if ($showQuickAdd)
                @can('create', \App\Models\Prospect::class)
                    <div class="border-b border-emerald-200 bg-emerald-50/50 p-3">
                        <livewire:prospects.prospect-create
                            :embedded="true"
                            :quick="true"
                            :modal="true"
                            :key="'dashboard-quick-add-'.$quickAddKey"
                        />
                    </div>
                @endcan
            @endif

            <div class="border-b border-slate-200 px-4 py-3">
                <label for="dashboard-prospects-search" class="sr-only">Search prospects</label>
                <input
                    id="dashboard-prospects-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, email, phone, or city..."
                    class="h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Prospect</th>
                            <th class="hidden px-4 py-2.5 sm:table-cell">Contact</th>
                            <th class="hidden px-4 py-2.5 md:table-cell">Stage</th>
                            <th class="px-4 py-2.5">Interest</th>
                            <th class="hidden px-4 py-2.5 lg:table-cell">Next follow-up</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($prospects as $prospect)
                            <tr wire:key="dashboard-prospect-{{ $prospect->id }}" class="transition hover:bg-gw-accent-soft/40">
                                <td class="px-4 py-2.5">
                                    <p class="font-semibold text-gw-primary">{{ $prospect->displayName() }}</p>
                                    <p class="text-xs text-slate-500">{{ $prospect->city ?? 'City not set' }} · {{ str($prospect->status)->title() }}</p>
                                </td>
                                <td class="hidden px-4 py-2.5 text-slate-600 sm:table-cell">
                                    <div>{{ $prospect->email ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $prospect->phone ?? '—' }}</div>
                                </td>
                                <td class="hidden px-4 py-2.5 text-slate-600 md:table-cell">{{ $prospect->stage?->name ?? 'No stage' }}</td>
                                <td class="px-4 py-2.5">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-red-100 text-red-700' => $prospect->interest_level === 'hot',
                                        'bg-amber-100 text-amber-800' => $prospect->interest_level === 'warm',
                                        'bg-slate-100 text-slate-700' => $prospect->interest_level === 'cold',
                                    ])>{{ str($prospect->interest_level)->title() }}</span>
                                </td>
                                <td class="hidden px-4 py-2.5 text-slate-600 lg:table-cell">{{ $prospect->next_follow_up_at?->format('M j, g:i A') ?? 'Not scheduled' }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex justify-end gap-1">
                                        @can('view', $prospect)
                                            <a
                                                href="{{ route('team.prospects.records.show', $prospect) }}"
                                                wire:click="close"
                                                title="View"
                                                aria-label="View {{ $prospect->displayName() }}"
                                                class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[11px] font-semibold text-sky-700 transition hover:bg-sky-100"
                                            >
                                                View
                                            </a>
                                        @endcan
                                        @can('update', $prospect)
                                            <a
                                                href="{{ route('team.prospects.records.edit', $prospect) }}"
                                                wire:click="close"
                                                title="Edit"
                                                aria-label="Edit {{ $prospect->displayName() }}"
                                                class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                            >
                                                Edit
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center">
                                    <p class="text-sm font-semibold text-gw-primary">No prospects found</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($search !== '')
                                            Try a different search or clear the filter.
                                        @else
                                            Use Quick Add to create your first prospect.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($prospects->hasPages())
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                    {{ $prospects->links() }}
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                <p class="text-xs text-slate-500">Open Prospect Summary for filters, pipeline insights, and full CRM tools.</p>
                <a
                    href="{{ $summaryUrl }}"
                    wire:click="close"
                    class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0E7490] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110"
                >
                    Open Prospect Summary
                </a>
            </div>
        </div>
    </div>
</div>
