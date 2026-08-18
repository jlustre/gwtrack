<div class="space-y-6" x-data="prospectKanbanBoard()">
    <div class="overflow-x-auto pb-4">
        <div class="flex min-w-max gap-4">
            @foreach ($stages as $stage)
                @php
                    $stageId = (int) $stage->pipeline_stage_id;
                    $stageProspects = $groupedProspects->get($stageId, collect());
                @endphp
                <div
                    wire:key="recruiting-stage-{{ $stageId }}"
                    class="w-72 shrink-0 rounded-lg border border-emerald-200/60 bg-gradient-to-b from-emerald-50/80 to-white shadow-sm"
                    x-on:dragover.prevent="dragOverColumn($event, {{ $stageId }})"
                    x-on:dragleave="dragLeaveColumn($event, {{ $stageId }})"
                    x-on:drop.prevent="dropOnColumn($event, {{ $stageId }})"
                    x-bind:class="dragOverStageId === {{ $stageId }} ? 'ring-2 ring-emerald-500 shadow-md' : ''"
                >
                    <div class="border-b border-emerald-200/50 bg-gw-primary px-4 py-3 text-white">
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
                            <span class="shrink-0 rounded-full bg-emerald-400 px-2 py-0.5 text-xs font-bold text-gw-primary">{{ $stageProspects->count() }}</span>
                        </div>
                    </div>

                    <div class="min-h-[12rem] space-y-3 p-3">
                        @forelse ($stageProspects as $prospect)
                            <article
                                wire:key="recruiting-prospect-{{ $prospect->id }}"
                                draggable="true"
                                x-on:dragstart="startDrag($event, @js($prospect->id))"
                                x-on:dragend="endDrag()"
                                class="cursor-grab rounded-lg border border-slate-200 bg-white p-2 shadow-sm active:cursor-grabbing hover:border-emerald-400/50"
                            >
                                <div class="flex items-start justify-between gap-1.5">
                                    <a
                                        href="{{ route('team.prospects.records.show', $prospect) }}"
                                        draggable="false"
                                        class="min-w-0 truncate text-sm font-semibold leading-tight text-gw-primary hover:text-emerald-700"
                                    >
                                        {{ $prospect->displayName() }}
                                    </a>
                                    <span class="shrink-0 rounded bg-emerald-100 px-1.5 py-px text-[9px] font-bold uppercase leading-none text-emerald-800">{{ str($prospect->interest_level)->title() }}</span>
                                </div>
                                @if ($prospect->next_follow_up_at)
                                    <p @class([
                                        'mt-1 truncate text-right text-[10px] leading-tight',
                                        'font-semibold text-red-600' => $prospect->next_follow_up_at->isPast(),
                                        'text-slate-500' => ! $prospect->next_follow_up_at->isPast(),
                                    ])>
                                        F/U {{ $prospect->next_follow_up_at->isPast() ? 'overdue' : 'due' }} {{ $prospect->next_follow_up_at->diffForHumans(null, true) }}
                                    </p>
                                @endif
                            </article>
                        @empty
                            <p class="py-6 text-center text-xs text-slate-400">No candidates in this stage</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('livewire.prospects.partials.pipeline-stage-info-modal')
</div>
