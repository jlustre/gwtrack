@if ($showStageInfoModal && filled($stageInfo))
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pipeline-stage-info-title"
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="closeStageInfo"></div>

        <div class="relative z-10 flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl">
            <div class="shrink-0 border-b border-gw-accent/30 bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-5 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        @if (filled($stageInfo['phase'] ?? null))
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">{{ $stageInfo['phase'] }}</p>
                        @else
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Pipeline Stage</p>
                        @endif
                        <h3 id="pipeline-stage-info-title" class="mt-0.5 text-xl font-semibold">{{ $stageInfo['title'] ?? 'Stage details' }}</h3>
                        @if (! empty($stageInfo['is_terminal']))
                            <span class="mt-2 inline-flex rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-100">Final outcome</span>
                        @endif
                    </div>
                    <button type="button" wire:click="closeStageInfo" class="text-2xl leading-none text-slate-300 hover:text-white" aria-label="Close stage information">&times;</button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <div class="space-y-5 text-sm text-slate-700">
                    @if (filled($stageInfo['summary'] ?? null))
                        <section>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Overview</h4>
                            <p class="mt-2 leading-relaxed">{{ $stageInfo['summary'] }}</p>
                        </section>
                    @endif

                    @if (! empty($stageInfo['objectives']))
                        <section>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Objectives</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($stageInfo['objectives'] as $objective)
                                    <li>{{ $objective }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($stageInfo['recommended_actions']))
                        <section>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Recommended actions</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($stageInfo['recommended_actions'] as $action)
                                    <li>{{ $action }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (filled($stageInfo['advance_when'] ?? null))
                        <section class="rounded-lg border border-gw-accent/25 bg-gw-accent-soft px-4 py-3">
                            <h4 class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">When to advance</h4>
                            <p class="mt-2 leading-relaxed text-gw-primary">{{ $stageInfo['advance_when'] }}</p>
                        </section>
                    @endif

                    @if (! empty($stageInfo['automation']))
                        <section>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Automation</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($stageInfo['automation'] as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (isset($stageInfo['conversion_weight']) && $stageInfo['conversion_weight'] !== null)
                        <section class="border-t border-slate-100 pt-4">
                            <p class="text-xs text-slate-500">
                                Conversion weight:
                                <span class="font-semibold text-gw-primary">{{ number_format((float) $stageInfo['conversion_weight'], 0) }}%</span>
                            </p>
                        </section>
                    @endif
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button
                    type="button"
                    wire:click="closeStageInfo"
                    class="w-full rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-[#d4ad5c]"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
@endif
