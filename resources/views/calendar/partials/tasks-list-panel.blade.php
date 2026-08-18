@php
    $panel = $panel ?? [];
    $items = $panel['items'] ?? [];
    $count = (int) ($panel['count'] ?? count($items));
    $title = $panel['title'] ?? 'Tasks List';
    $summary = $panel['summary'] ?? null;
    $emptyMessage = $panel['empty_message'] ?? 'No tasks due today';
    $restricted = (bool) ($panel['restricted'] ?? false);
    $compact = (bool) ($compact ?? true);
@endphp

<section @class(['rounded-xl border border-slate-200 bg-white shadow-sm', 'p-4' => $compact, 'p-5' => ! $compact])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h2>
            @if ($summary)
                <p class="mt-1 text-sm text-slate-600">{{ $summary }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="Livewire.dispatch('open-my-tasks-modal')" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-muted">
                My tasks
            </button>
            @if (! $restricted)
                <a href="{{ route('tasks.index') }}" wire:navigate class="rounded-md bg-gradient-to-br from-gw-primary via-[#1E3A8A] to-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white hover:brightness-110">
                    View all
                </a>
            @endif
        </div>
    </div>

    @if ($restricted)
        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
            {{ $emptyMessage }}
        </div>
    @elseif ($items === [])
        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
            {{ $emptyMessage }}
        </div>
    @else
        <ul @class(['mt-4 space-y-2', 'max-h-56 overflow-y-auto pr-1' => $compact])>
            @foreach ($items as $item)
                <li wire:key="calendar-task-item-{{ md5(($item['title'] ?? '').($item['meta'] ?? '')) }}" @class([
                    'rounded-lg border px-3 py-2.5',
                    ($item['highlight'] ?? false) ? 'border-gw-accent/40 bg-[#FFFDF5]' : 'border-slate-200 bg-slate-50',
                ])>
                    @if (filled($item['url'] ?? null))
                        <a href="{{ $item['url'] }}" wire:navigate class="block">
                            @include('dashboard.partials.activity-item-content', ['item' => $item])
                        </a>
                    @else
                        @include('dashboard.partials.activity-item-content', ['item' => $item])
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($count > count($items))
            <p class="mt-2 text-xs text-slate-500">{{ $count - count($items) }} more due today — open My tasks to review.</p>
        @endif
    @endif
</section>
