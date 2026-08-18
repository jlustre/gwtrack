<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="todays-agenda-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div class="relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl">
            <div class="shrink-0 border-b border-[#0F766E]/40 bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-5 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Today's Agenda</p>
                        <h3 id="todays-agenda-modal-title" class="mt-0.5 text-xl font-semibold">{{ $agenda['date_label'] ?? now()->format('l, M j') }}</h3>
                        <p class="mt-1 text-sm text-slate-200">
                            @if (($agenda['total'] ?? 0) > 0)
                                {{ $agenda['total'] }} open item{{ ($agenda['total'] ?? 0) === 1 ? '' : 's' }} across your tasks, schedule, and trackers.
                            @else
                                You're all caught up for now. Expand a category below to review or plan ahead.
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white" aria-label="Close today's agenda">&times;</button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <div class="space-y-4">
                    @forelse ($agenda['sections'] ?? [] as $section)
                        @php
                            $sectionKey = (string) ($section['key'] ?? $loop->index);
                            $isCollapsed = (bool) ($collapsedSections[$sectionKey] ?? false);
                            $itemCount = (int) ($section['count'] ?? 0);
                        @endphp
                        <section wire:key="agenda-section-{{ $sectionKey }}" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-stretch border-b border-slate-100 bg-slate-50">
                                <button
                                    type="button"
                                    wire:click="toggleSection('{{ $sectionKey }}')"
                                    class="flex min-w-0 flex-1 items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-slate-100/80"
                                    aria-expanded="{{ $isCollapsed ? 'false' : 'true' }}"
                                    aria-controls="agenda-section-panel-{{ $sectionKey }}"
                                >
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        @include('livewire.dashboard.partials.todays-agenda-section-icon', ['icon' => $section['icon'] ?? 'calendar'])
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-gw-primary">{{ $section['label'] }}</h4>
                                            <p class="text-xs text-slate-500">{{ $itemCount > 0 ? $itemCount.' open' : 'Nothing open' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span @class([
                                            'inline-flex min-w-[1.75rem] items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold',
                                            'bg-gw-accent-muted text-gw-primary' => $itemCount > 0,
                                            'bg-slate-100 text-slate-500' => $itemCount === 0,
                                        ])>{{ $itemCount }}</span>
                                        <svg
                                            @class([
                                                'h-4 w-4 text-slate-500 transition-transform duration-200',
                                                'rotate-180' => ! $isCollapsed,
                                            ])
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            @if (! $isCollapsed)
                                <div id="agenda-section-panel-{{ $sectionKey }}">
                                    @if ($itemCount > 0)
                                        <ul class="divide-y divide-slate-100">
                                            @foreach ($section['items'] as $item)
                                                <li wire:key="{{ $item['id'] ?? $loop->index }}">
                                                    @if (filled($item['url'] ?? null))
                                                        <a href="{{ $item['url'] }}" class="group block px-4 py-3 transition hover:bg-gw-accent-soft" wire:click="close">
                                                            @include('dashboard.partials.activity-item-content', ['item' => $item])
                                                        </a>
                                                    @else
                                                        <div class="px-4 py-3">
                                                            @include('dashboard.partials.activity-item-content', ['item' => $item])
                                                        </div>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="px-4 py-4 text-sm text-slate-500">{{ $section['empty_message'] ?? 'Nothing scheduled.' }}</p>
                                    @endif

                                    @if (! empty($section['action']))
                                        <div class="border-t border-slate-100 bg-slate-50 px-4 py-2">
                                            @if (($section['action']['url'] ?? '') === '#schedule-phone-call')
                                                <button
                                                    type="button"
                                                    class="text-xs font-semibold text-[#0F766E] hover:underline"
                                                    wire:click="close"
                                                    onclick="Livewire.dispatch('open-dashboard-schedule-phone-call-modal')"
                                                >
                                                    {{ $section['action']['label'] }}
                                                </button>
                                            @elseif (($section['action']['event'] ?? '') === 'open-my-tasks-modal')
                                                <button
                                                    type="button"
                                                    class="text-xs font-semibold text-[#0F766E] hover:underline"
                                                    wire:click="close"
                                                    onclick="Livewire.dispatch('open-my-tasks-modal')"
                                                >
                                                    {{ $section['action']['label'] }}
                                                </button>
                                            @else
                                                <a href="{{ $section['action']['url'] }}" class="text-xs font-semibold text-[#0F766E] hover:underline" wire:click="close">
                                                    {{ $section['action']['label'] }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                            <p class="text-sm font-semibold text-gw-primary">No agenda categories available</p>
                            <p class="mt-1 text-sm text-slate-500">Your role may not have scheduled items configured yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-5 py-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            wire:click="expandAllSections"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Expand all
                        </button>
                        <button
                            type="button"
                            wire:click="collapseAllSections"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Collapse all
                        </button>
                    </div>
                    <button
                        type="button"
                        wire:click="close"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
