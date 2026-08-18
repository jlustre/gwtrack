<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin"
        title="CRM Pipelines &amp; Stages"
        description="Manage independent funnels and their stages. Stages belong to one pipeline only and never replace another funnel's progress."
        class="rounded-xl"
    >
        <x-slot:actions>
            @if ($canUpdateSeeder)
                <button
                    type="button"
                    wire:click="updateSeeder"
                    wire:loading.attr="disabled"
                    wire:target="updateSeeder"
                    class="inline-flex items-center justify-center rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="updateSeeder">Update Seeder</span>
                    <span wire:loading wire:target="updateSeeder">Updating…</span>
                </button>
            @endif
            <a
                href="{{ route('crm.index') }}"
                class="inline-flex items-center justify-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15"
            >
                Open CRM Dashboard
            </a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if ($statusMessage)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ str($statusMessage)->replace('-', ' ')->title() }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gw-primary">Pipelines</h2>
                <div class="mt-3 space-y-1">
                    @foreach ($pipelines as $pipeline)
                        <button
                            type="button"
                            wire:click="selectPipeline({{ $pipeline->id }})"
                            @class([
                                'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm',
                                'bg-gw-accent-soft border border-gw-accent' => $selectedPipelineId === $pipeline->id,
                                'hover:bg-slate-50' => $selectedPipelineId !== $pipeline->id,
                            ])
                        >
                            <span class="font-semibold text-gw-primary">{{ $pipeline->short_name ?: $pipeline->name }}</span>
                            <span class="text-[11px] {{ $pipeline->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $pipeline->is_active ? 'Active' : 'Off' }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <form wire:submit="savePipeline" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gw-primary">Add / update pipeline</h2>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Name</label>
                    <input wire:model="pipelineName" type="text" class="w-full rounded-md border-slate-300 text-sm">
                    <x-input-error :messages="$errors->get('pipelineName')" class="mt-1" />
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Key</label>
                    <input wire:model="pipelineKey" type="text" placeholder="lead-prospect" class="w-full rounded-md border-slate-300 text-sm">
                    <x-input-error :messages="$errors->get('pipelineKey')" class="mt-1" />
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Short name</label>
                    <input wire:model="pipelineShortName" type="text" class="w-full rounded-md border-slate-300 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Color</label>
                    <input wire:model="pipelineColor" type="color" class="h-10 w-full rounded-md border-slate-300">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Description</label>
                    <textarea wire:model="pipelineDescription" rows="3" class="w-full rounded-md border-slate-300 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full rounded-md bg-gw-primary px-3 py-2 text-xs font-semibold text-white">Save Pipeline</button>
            </form>
        </aside>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @if ($selected)
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gw-primary">{{ $selected->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $selected->description }}</p>
                        <p class="mt-2 text-xs text-slate-500">Key: {{ $selected->key }} · {{ $selected->stages->count() }} stages</p>
                    </div>
                    <button
                        type="button"
                        wire:click="togglePipeline({{ $selected->id }})"
                        class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-gw-primary"
                    >
                        {{ $selected->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </div>

                <form wire:submit="addStage" class="mt-4 grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_auto_auto]">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">New stage name</label>
                        <input wire:model="stageName" type="text" class="w-full rounded-md border-slate-300 text-sm">
                        <x-input-error :messages="$errors->get('stageName')" class="mt-1" />
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="stageIsTerminal" class="rounded border-slate-300">
                        Terminal stage
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="rounded-md bg-gw-primary px-4 py-2 text-xs font-semibold text-white">Add Stage</button>
                    </div>
                </form>

                <div class="relative z-0 mt-4 divide-y divide-slate-100 overflow-visible rounded-md border border-slate-200">
                    @forelse ($selected->stages as $stage)
                        <div
                            class="relative z-0 flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:z-40 focus-within:z-40"
                            wire:key="stage-{{ $stage->id }}"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <p class="min-w-0 text-sm font-semibold text-gw-primary">
                                        {{ $stage->sort_order / 10 }}. {{ $stage->name }}
                                    </p>
                                    <x-checklist-description-help
                                        class="mt-0.5"
                                        :text="$stage->helpDescription($selected)"
                                        placement="left"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    {{ $stage->slug }}
                                    @if ($stage->is_terminal) · Terminal @endif
                                    · {{ $stage->is_active ? 'Active' : 'Inactive' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="moveStage({{ $stage->id }}, 'up')" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold">Up</button>
                                <button type="button" wire:click="moveStage({{ $stage->id }}, 'down')" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold">Down</button>
                                <button type="button" wire:click="toggleStage({{ $stage->id }})" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold">
                                    {{ $stage->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="deleteStage({{ $stage->id }})"
                                    wire:confirm="Delete stage “{{ $stage->name }}”? This removes it from the pipeline list."
                                    class="rounded border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">No stages yet. Add the first stage above.</p>
                    @endforelse
                </div>
            @else
                <p class="py-16 text-center text-sm text-slate-500">Select a pipeline to manage its stages.</p>
            @endif
        </section>
    </div>
</div>
