<div class="space-y-6">
    <section class="gw-page-hero overflow-hidden rounded-lg border border-gw-primary bg-gw-primary px-6 py-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('crm.index') }}" wire:navigate class="text-sm font-semibold text-gw-accent hover:underline">← CRM Funnels</a>
                <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-gw-accent">CRM Funnel System</p>
                <h1 class="mt-2 text-2xl font-semibold text-white">{{ $pipeline->name }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $pipeline->description }}</p>
            </div>
            <a href="{{ route('crm.consultants') }}" wire:navigate class="rounded-md border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                Open Consultant Tracker
            </a>
        </div>
    </section>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($columns as $column)
            <div class="w-72 shrink-0 rounded-lg border border-slate-200 bg-slate-50">
                <div class="border-b border-slate-200 px-3 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gw-primary">{{ $column['stage']->name }}</p>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                            {{ $column['participations']->count() }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2 p-3">
                    @forelse ($column['participations'] as $participation)
                        <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
                            <p class="text-sm font-semibold text-gw-primary">{{ $participation->user?->name }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $participation->user?->email }}</p>
                            <p class="mt-2 text-[11px] text-slate-500">
                                Status: {{ str($participation->status)->title() }}
                                · {{ $participation->progress_percent }}%
                            </p>
                            @can('manage crm funnels')
                                <div class="mt-3">
                                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Move to</label>
                                    <select
                                        class="w-full rounded-md border-slate-300 text-xs"
                                        wire:change="moveParticipation({{ $participation->id }}, $event.target.value)"
                                    >
                                        @foreach ($columns as $option)
                                            <option value="{{ $option['stage']->id }}" @selected($option['stage']->id === $participation->crm_pipeline_stage_id)>
                                                {{ $option['stage']->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <p class="px-1 py-6 text-center text-xs text-slate-400">No active members in this stage</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
