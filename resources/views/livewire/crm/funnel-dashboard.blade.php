<div class="space-y-6">
    <section class="gw-page-hero overflow-hidden rounded-lg border border-gw-primary bg-gw-primary px-6 py-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">CRM Funnel System</p>
                <h1 class="mt-2 text-2xl font-semibold text-white">Multi-Pipeline Dashboard</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    Each funnel is independent. A member can be active in several pipelines at the same time without one replacing another.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('crm.consultants') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                    Consultant Tracker
                </a>
                @can('manage crm funnels')
                    <a href="{{ route('admin.crm.pipelines') }}" class="rounded-md border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                        Manage Pipelines
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($pipelines as $row)
            <a
                href="{{ route('crm.funnels.show', $row['pipeline']->key) }}"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-gw-accent"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            {{ $row['pipeline']->short_name ?: $row['pipeline']->name }}
                        </p>
                        <h2 class="mt-1 text-lg font-semibold text-gw-primary">{{ $row['pipeline']->name }}</h2>
                    </div>
                    <span class="h-3 w-3 rounded-full" style="background: {{ $row['pipeline']->color }}"></span>
                </div>
                <p class="mt-3 text-xs leading-5 text-slate-600">{{ $row['pipeline']->description }}</p>
                <div class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-500">
                    <span>{{ $row['stage_count'] }} stages</span>
                    <span class="text-gw-primary">{{ number_format($row['active_count']) }} active</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
