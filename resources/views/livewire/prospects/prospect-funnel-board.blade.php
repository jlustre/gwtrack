<div class="space-y-6" x-data="prospectKanbanBoard()">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Sales Pipeline</h1>
                <p class="mt-2 text-sm text-slate-200">Drag sales prospects between stages. Insurance and dual-pipeline records are shown.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('team.recruiting.index') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Recruiting Pipeline</a>
                <a href="{{ route('team.prospects.create') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">+ Add Prospect</a>
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
            </div>
        </div>
    </div>

    <div wire:loading.delay.class="opacity-60" class="overflow-x-auto pb-4 transition-opacity">
        <div class="flex min-w-max gap-4">
            @foreach ($stages as $stage)
                @php
                    $stageId = (int) $stage->pipeline_stage_id;
                    $stageProspects = $groupedProspects->get($stageId, collect());
                @endphp
                <div
                    wire:key="stage-{{ $stageId }}"
                    class="w-72 shrink-0 rounded-lg border border-gw-accent/30 bg-gradient-to-b from-gw-accent-soft to-white shadow-sm transition-shadow"
                    x-on:dragover.prevent="dragOverColumn($event, {{ $stageId }})"
                    x-on:dragleave="dragLeaveColumn($event, {{ $stageId }})"
                    x-on:drop.prevent="dropOnColumn($event, {{ $stageId }})"
                    x-bind:class="dragOverStageId === {{ $stageId }} ? 'ring-2 ring-gw-accent shadow-md' : ''"
                >
                    <div class="border-b border-gw-accent/20 bg-gw-primary px-4 py-3 text-white">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <h2 class="truncate text-sm font-semibold">{{ $stage->name }}</h2>
                                <button
                                    type="button"
                                    draggable="false"
                                    wire:click.stop="openStageInfo({{ $stage->id }})"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md bg-lime-300 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-950 shadow-sm ring-1 ring-lime-400 transition hover:bg-lime-200 hover:ring-lime-500"
                                    title="Stage information"
                                    aria-label="More information about {{ $stage->name }}"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Info
                                </button>
                            </div>
                            <span class="shrink-0 rounded-full bg-gw-accent px-2 py-0.5 text-xs font-bold text-gw-primary">{{ $stageProspects->count() }}</span>
                        </div>
                    </div>

                    <div class="space-y-3 p-3 min-h-[12rem]">
                        @forelse ($stageProspects as $prospect)
                            <article
                                wire:key="prospect-{{ $prospect->id }}"
                                draggable="true"
                                x-on:dragstart="startDrag($event, @js($prospect->id))"
                                x-on:dragend="endDrag()"
                                x-bind:class="draggingProspectId === @js($prospect->id) ? 'opacity-50 ring-2 ring-gw-accent' : ''"
                                class="cursor-grab rounded-lg border border-slate-200 bg-white p-2 shadow-sm active:cursor-grabbing hover:border-gw-accent/50"
                            >
                                <div class="flex items-stretch gap-1">
                                    <button
                                        type="button"
                                        draggable="false"
                                        class="shrink-0 self-start pt-0.5 text-slate-300 hover:text-gw-accent-dark"
                                        title="Drag to move"
                                        tabindex="-1"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M7 4a1 1 0 100 2 1 1 0 000-2zM7 9a1 1 0 100 2 1 1 0 000-2zM7 14a1 1 0 100 2 1 1 0 000-2zM13 4a1 1 0 100 2 1 1 0 000-2zM13 9a1 1 0 100 2 1 1 0 000-2zM13 14a1 1 0 100 2 1 1 0 000-2z"/>
                                        </svg>
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-1.5">
                                            <a
                                                href="{{ route('team.prospects.records.show', $prospect) }}"
                                                draggable="false"
                                                class="min-w-0 truncate text-sm font-semibold leading-tight text-gw-primary hover:text-gw-accent-dark"
                                            >
                                                {{ $prospect->displayName() }}
                                            </a>
                                            <div class="flex shrink-0 flex-col items-end gap-0.5">
                                                <span class="rounded bg-gw-accent-muted px-1.5 py-px text-[9px] font-bold uppercase leading-none text-gw-accent-dark">{{ str($prospect->interest_level)->title() }}</span>
                                                @if ($prospect->priority === 'urgent' || $prospect->priority === 'high')
                                                    <span class="rounded bg-red-100 px-1.5 py-px text-[9px] font-bold uppercase leading-none text-red-700">{{ str($prospect->priority)->title() }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-1 flex items-center justify-between gap-1.5">
                                            <div class="flex min-w-0 items-center gap-1 text-[10px] leading-tight text-slate-500">
                                                @if ($prospect->phone)
                                                    <a
                                                        href="tel:{{ preg_replace('/[^\d+]/', '', $prospect->phone) }}"
                                                        draggable="false"
                                                        class="shrink-0 text-gw-accent-dark hover:text-gw-primary"
                                                        title="Call {{ $prospect->phone }}"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                    </a>
                                                @endif
                                                @if ($prospect->next_follow_up_at)
                                                    <span @class([
                                                        'truncate',
                                                        'font-semibold text-red-600' => $prospect->next_follow_up_at->isPast(),
                                                    ])>
                                                        F/U {{ $prospect->next_follow_up_at->isPast() ? 'overdue' : 'due' }} {{ $prospect->next_follow_up_at->diffForHumans(null, true) }}
                                                    </span>
                                                @elseif (! $prospect->phone)
                                                    <span class="text-slate-400">No follow-up</span>
                                                @endif
                                            </div>

                                            <div class="flex shrink-0 flex-col gap-0.5">
                                                <button
                                                    type="button"
                                                    draggable="false"
                                                    wire:click="$dispatch('open-log-activity-modal', { prospectId: '{{ $prospect->id }}', activityType: 'phone_call' })"
                                                    class="rounded border border-slate-200 px-1.5 py-px text-[9px] font-semibold leading-tight text-slate-600 hover:bg-gw-accent-soft"
                                                >
                                                    Call
                                                </button>
                                                <button
                                                    type="button"
                                                    draggable="false"
                                                    wire:click="$dispatch('open-log-activity-modal', { prospectId: '{{ $prospect->id }}' })"
                                                    class="rounded border border-slate-200 px-1.5 py-px text-[9px] font-semibold leading-tight text-slate-600 hover:bg-gw-accent-soft"
                                                >
                                                    Log
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-center text-xs text-slate-400 py-6">Drop prospects here</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <livewire:prospects.log-activity-modal />
    <livewire:prospects.log-communication-modal />

    @include('livewire.prospects.partials.pipeline-stage-info-modal')

    @include('prospects.partials.mobile-quick-actions')
</div>
