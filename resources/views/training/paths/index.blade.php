<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <h1 class="mt-2 text-3xl font-semibold">Learning Paths</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Structured development journeys that combine academy courses into role-based programs.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($rows as $row)
                @php
                    $path = $row['path'];
                    $statusLabel = str($row['status'])->replace('_', ' ')->title();
                @endphp
                <a href="{{ route('training.paths.show', $path) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-gw-accent/40 hover:bg-gw-accent-soft">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gw-primary">{{ $path->name }}</h2>
                            @if ($path->audience)
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ str($path->audience)->replace('_', ' ')->title() }}</p>
                            @endif
                            <p class="mt-2 text-sm text-slate-600">{{ $path->description }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ $row['module_count'] }} courses · {{ $statusLabel }}</p>
                        </div>
                        <span class="rounded-full bg-gw-primary px-2.5 py-1 text-xs font-bold text-gw-accent">{{ $row['progress_percent'] }}%</span>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $row['progress_percent'] }}%"></div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-600 md:col-span-2">Learning paths will appear here once published by your administrator.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
