<div class="space-y-6">
    <section
        class="overflow-hidden rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary px-6 py-6 text-white shadow-lg"
        style="background-color: #2D3E50;"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">CRM Funnel System</p>
                <h1 class="mt-2 text-2xl font-semibold text-white">Pipelines &amp; Stages</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    Compare funnel stages side by side. Each column is one pipeline — pick another pipeline in any column to change the comparison.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="addColumn"
                    @disabled(count($columns) >= 4)
                    class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Add column
                </button>
                @can('manage crm funnels')
                    <a
                        href="{{ route('admin.crm.pipelines') }}"
                        class="rounded-md border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                    >
                        Manage pipelines
                    </a>
                @endcan
            </div>
        </div>
    </section>

    @if ($pipelines->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">No active pipelines</h2>
            <p class="mt-2 text-sm text-slate-600">Activate or create pipelines before comparing stages.</p>
        </div>
    @else
        <div
            class="grid gap-4"
            style="grid-template-columns: repeat({{ max(1, $columns->count()) }}, minmax(220px, 1fr));"
        >
            @foreach ($columns as $index => $column)
                @php
                    $pipeline = $column['pipeline'];
                    $stages = $column['stages'];
                    $accent = $pipeline?->color ?: '#2D3E50';
                @endphp
                <section
                    wire:key="compare-col-{{ $index }}-{{ $column['pipeline_id'] ?? 'empty' }}"
                    class="relative z-0 flex min-h-[28rem] flex-col overflow-visible rounded-lg border border-slate-200 bg-white shadow-sm hover:z-30 focus-within:z-30"
                >
                    <header class="border-b border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Column {{ $index + 1 }} pipeline
                                </label>
                                <select
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm font-semibold text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                    wire:model.live="columnPipelineIds.{{ $index }}"
                                >
                                    <option value="">Select pipeline…</option>
                                    @foreach ($pipelines as $option)
                                        <option value="{{ $option->id }}">
                                            {{ $option->short_name ?: $option->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($columns->count() > 2)
                                <button
                                    type="button"
                                    wire:click="removeColumn({{ $index }})"
                                    class="mt-5 shrink-0 rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100"
                                    title="Remove column"
                                >
                                    Remove
                                </button>
                            @endif
                        </div>

                        @if ($pipeline)
                            <div class="mt-3 flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $accent }}"></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gw-primary">{{ $pipeline->name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $stages->count() }} stages</p>
                                </div>
                            </div>
                        @endif
                    </header>

                    <div class="flex-1">
                        @forelse ($stages as $stage)
                            <div
                                @class([
                                    'gw-checklist-item relative z-0 flex items-start gap-3 !px-4 !py-3 hover:z-40 focus-within:z-40',
                                    'gw-checklist-item--odd' => $loop->odd,
                                    'gw-checklist-item--even' => $loop->even,
                                ])
                            >
                                <span
                                    class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white"
                                    style="background: {{ $stage->color ?: $accent }}"
                                >
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start gap-2">
                                        <p class="min-w-0 flex-1 text-sm font-semibold text-gw-primary">{{ $stage->name }}</p>
                                        <x-checklist-description-help
                                            class="mt-0.5"
                                            :text="$stage->helpDescription($pipeline)"
                                            :placement="$index === $columns->count() - 1 ? 'left' : 'right'"
                                        />
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @if ($stage->is_terminal)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Terminal</span>
                                        @endif
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                            {{ $stage->slug }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-center text-sm text-slate-500">
                                @if ($pipeline)
                                    No active stages in this pipeline.
                                @else
                                    Select a pipeline to view its stages.
                                @endif
                            </div>
                        @endforelse

                        @if ($pipeline && $stages->count() < $maxStageRows)
                            @for ($pad = $stages->count(); $pad < $maxStageRows; $pad++)
                                <div
                                    @class([
                                        'gw-checklist-item !px-4 !py-3 text-sm text-slate-300',
                                        'gw-checklist-item--odd' => $pad % 2 === 0,
                                        'gw-checklist-item--even' => $pad % 2 === 1,
                                    ])
                                >
                                    —
                                </div>
                            @endfor
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
