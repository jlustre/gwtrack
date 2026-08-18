@php
    $indicatorClasses = [
        'gray' => 'bg-slate-200 text-slate-700',
        'blue' => 'bg-sky-100 text-sky-800',
        'yellow' => 'bg-amber-100 text-amber-800',
        'orange' => 'bg-orange-100 text-orange-800',
        'green' => 'bg-emerald-100 text-emerald-800',
        'red' => 'bg-red-100 text-red-800',
    ];
@endphp

<div class="space-y-6">
    <section class="gw-page-hero rounded-lg border border-gw-primary bg-gw-primary px-6 py-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">CRM Funnel System</p>
                <h1 class="mt-2 text-2xl font-semibold text-white">Consultant Pipeline Tracker</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    Select a member consultant to see where they are on every independent pipeline at once.
                </p>
            </div>
            <a href="{{ route('crm.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                Back to CRM Dashboard
            </a>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <label for="crm-member-search" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                Select member consultant
            </label>
            <input
                id="crm-member-search"
                type="search"
                wire:model.live.debounce.300ms="q"
                placeholder="Search name or email…"
                class="w-full rounded-md border-slate-300 text-sm"
            >
            <div class="mt-3 max-h-[32rem] space-y-1 overflow-y-auto">
                @forelse ($members as $member)
                    <button
                        type="button"
                        wire:click="selectMember({{ $member->id }})"
                        @class([
                            'w-full rounded-md px-3 py-2 text-left transition',
                            'bg-gw-accent-soft border border-gw-accent' => $memberId === $member->id,
                            'hover:bg-slate-50 border border-transparent' => $memberId !== $member->id,
                        ])
                    >
                        <p class="text-sm font-semibold text-gw-primary">{{ $member->name }}</p>
                        <p class="text-[11px] text-slate-500">{{ $member->email }}</p>
                    </button>
                @empty
                    <p class="px-2 py-8 text-center text-xs text-slate-500">No visible members found.</p>
                @endforelse
            </div>
        </aside>

        <section class="space-y-4">
            @if ($overview)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-gw-primary">{{ $overview['member']->name }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $overview['member']->email }}</p>
                        </div>
                        <div class="flex gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-800">{{ $overview['active_count'] }} active</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">{{ $overview['completed_count'] }} completed</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($overview['pipelines'] as $row)
                        @php
                            $stages = $pipelineStages->get($row['key'])?->stages ?? collect();
                        @endphp
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" wire:key="pipeline-card-{{ $row['key'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $row['short_name'] }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-gw-primary">{{ $row['name'] }}</h3>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $indicatorClasses[$row['indicator']] ?? $indicatorClasses['gray'] }}">
                                    {{ $row['enrolled'] ? str($row['state'])->replace('_', ' ')->title() : 'Not Started' }}
                                </span>
                            </div>

                            <p class="mt-3 text-sm text-slate-700">
                                {{ $row['stage'] ?: 'Not enrolled in this pipeline' }}
                            </p>
                            <div class="mt-3 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" style="width: {{ $row['progress_percent'] }}%; background: {{ $row['color'] }}"></div>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">{{ $row['progress_percent'] }}% · Owner {{ $row['owner'] ?: 'Unassigned' }}</p>

                            @can('manage crm funnels')
                                <div class="mt-4 space-y-2">
                                    @if (! $row['enrolled'])
                                        <button
                                            type="button"
                                            wire:click="enroll('{{ $row['key'] }}')"
                                            class="w-full rounded-md border border-gw-primary bg-gw-primary px-3 py-2 text-xs font-semibold text-white"
                                        >
                                            Enroll in {{ $row['short_name'] }}
                                        </button>
                                    @else
                                        <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Current stage</label>
                                        <select
                                            class="w-full rounded-md border-slate-300 text-xs"
                                            wire:change="moveStage('{{ $row['key'] }}', $event.target.value)"
                                        >
                                            @foreach ($stages as $stage)
                                                <option value="{{ $stage->id }}" @selected($stage->id === $row['stage_id'])>
                                                    {{ $stage->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endcan

                            <a href="{{ route('crm.funnels.show', $row['key']) }}" class="mt-3 inline-flex text-xs font-semibold text-gw-accent-dark hover:underline">
                                Open {{ $row['short_name'] }} board →
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-lg font-semibold text-gw-primary">Select a member consultant</p>
                    <p class="mt-2 text-sm text-slate-600">Choose someone from the list to view their status across every CRM funnel.</p>
                </div>
            @endif
        </section>
    </div>
</div>
