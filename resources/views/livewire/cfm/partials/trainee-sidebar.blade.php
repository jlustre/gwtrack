<aside class="relative min-w-0 xl:min-w-[20rem]" x-data="{ open: @entangle('sidebarOpen') }">
    <div
        x-show="open"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-40 bg-slate-900/50 xl:hidden"
        wire:click="$set('sidebarOpen', false)"
    ></div>

    <div
        class="fixed inset-y-0 left-0 z-50 flex w-[min(100vw-2rem,20rem)] flex-col overflow-hidden border-r border-slate-200 bg-white shadow-xl transition-transform duration-300 xl:static xl:z-auto xl:w-full xl:rounded-xl xl:border xl:shadow-sm"
        :class="open ? 'translate-x-0' : '-translate-x-full xl:translate-x-0'"
    >
        <div class="flex items-center justify-between border-b border-slate-200 bg-gw-primary px-4 py-4 text-white xl:rounded-t-xl">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Trainees</h2>
                <p class="mt-0.5 text-xs text-slate-300">{{ $trainees->count() }} shown · {{ $summary['total_trainees'] }} total</p>
            </div>
            <button
                type="button"
                wire:click="$set('sidebarOpen', false)"
                class="rounded p-1 text-slate-300 hover:text-white xl:hidden"
                aria-label="Close trainee list"
            >&times;</button>
        </div>

        <div class="space-y-3 border-b border-slate-200 p-4">
            <label class="sr-only" for="trainee-search">Search trainees</label>
            <input
                id="trainee-search"
                type="search"
                wire:model.live.debounce.300ms="traineeSearch"
                placeholder="Search by name..."
                class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >

            <div class="flex flex-wrap gap-1.5">
                @foreach ([
                    'all' => 'All',
                    'active' => 'Active',
                    'new' => 'New',
                    'at_risk' => 'At Risk',
                    'licensing' => 'Licensing',
                    'fap' => 'FAP',
                    'promotion_ready' => 'Ready',
                    'inactive' => 'Inactive',
                ] as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('traineeFilter', @js($key))"
                        @class([
                            'rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide transition',
                            'bg-gw-accent text-gw-primary' => $traineeFilter === $key,
                            'bg-slate-100 text-slate-600 hover:bg-slate-200' => $traineeFilter !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="min-h-[16rem] flex-1 overflow-y-auto p-2 xl:min-h-0 xl:max-h-[calc(100vh-16rem)]">
            @if ($trainees->isEmpty())
                <p class="px-2 py-6 text-center text-sm text-slate-500">No trainees match this filter.</p>
            @else
                <ul class="space-y-1">
                    @foreach ($trainees as $trainee)
                        <li wire:key="trainee-{{ $trainee['id'] }}">
                            <button
                                type="button"
                                wire:click="selectTrainee({{ $trainee['id'] }})"
                                @class([
                                    'flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition',
                                    'border-gw-accent/40 bg-gw-accent-soft shadow-sm' => $selectedTraineeId === $trainee['id'],
                                    'border-transparent hover:border-slate-200 hover:bg-slate-50' => $selectedTraineeId !== $trainee['id'],
                                ])
                            >
                                @if ($trainee['photo_url'])
                                    <img src="{{ $trainee['photo_url'] }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white">
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent">
                                        {{ $trainee['initials'] }}
                                    </span>
                                @endif

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-gw-primary">{{ $trainee['name'] }}</span>
                                    <span class="mt-0.5 flex flex-wrap items-center gap-1 text-[0.65rem] text-slate-500">
                                        <span>{{ $trainee['rank'] }}</span>
                                        <span>·</span>
                                        <span>FAP {{ $trainee['fap_percent'] }}%</span>
                                        <span>·</span>
                                        <span>LIC {{ $trainee['licensing_percent'] }}%</span>
                                    </span>
                                </span>

                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide',
                                    'bg-emerald-100 text-emerald-800' => $trainee['status'] === 'active',
                                    'bg-sky-100 text-sky-800' => $trainee['status'] === 'new',
                                    'bg-red-100 text-red-800' => $trainee['status'] === 'at_risk',
                                    'bg-amber-100 text-amber-800' => in_array($trainee['status'], ['licensing', 'fap'], true),
                                    'bg-gw-accent/20 text-gw-accent-dark' => $trainee['status'] === 'promotion_ready',
                                    'bg-slate-100 text-slate-600' => $trainee['status'] === 'inactive',
                                ])>
                                    {{ $trainee['status_label'] }}
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($selectedTraineeId)
            <div class="border-t border-slate-200 p-3">
                <button
                    type="button"
                    wire:click="selectTrainee(null)"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Clear selection · Dashboard view
                </button>
            </div>
        @endif
    </div>
</aside>
