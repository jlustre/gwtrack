@php
    $onboarding = $onboarding ?? [];
    $items = $onboarding['preview_items'] ?? [];
    $total = (int) ($onboarding['total'] ?? 0);
    $completed = (int) ($onboarding['completed'] ?? 0);
    $percent = (int) ($onboarding['percent'] ?? 0);
    $route = $onboarding['route'] ?? 'onboarding.index';
    $hasMore = (bool) ($onboarding['has_more'] ?? ($total > count($items)));
    $started = (bool) ($onboarding['started'] ?? false);
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Onboarding Checklist</h2>
            @if ($started && $total > 0)
                <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ $completed }} of {{ $total }} items complete · {{ $percent }}%
                </p>
            @elseif ($total > 0)
                <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ $total }} applicable item{{ $total === 1 ? '' : 's' }} · Not started yet
                </p>
            @else
                <p class="mt-1 text-xs font-medium text-slate-500">No onboarding items are configured yet.</p>
            @endif
        </div>

        @if ($route && $total > 0)
            <a
                href="{{ route($route) }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-gw-accent/50 bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
            >
                View complete list
                @if ($hasMore)
                    <span class="font-normal text-slate-600">({{ $total }})</span>
                @endif
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                </svg>
            </a>
        @endif
    </div>

    @if ($started && $total > 0)
        <div class="mb-4">
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-2 rounded-full bg-gw-accent transition-all duration-300" style="width: {{ max(0, min(100, $percent)) }}%"></div>
            </div>
        </div>
    @endif

    <ul class="space-y-2">
        @forelse ($items as $item)
            <li class="flex items-center justify-between gap-3 rounded-md border border-slate-100 bg-slate-50 px-3 py-2.5">
                <span @class([
                    'min-w-0 text-sm font-medium',
                    'text-slate-500 line-through' => $item['is_completed'] ?? false,
                    'text-gw-primary' => ! ($item['is_completed'] ?? false),
                ])>{{ $item['title'] }}</span>
                <span @class([
                    'shrink-0 rounded-full px-2 py-0.5 text-[0.68rem] font-semibold uppercase tracking-wide',
                    'bg-emerald-50 text-emerald-700' => ($item['status'] ?? '') === 'Completed',
                    'bg-amber-50 text-amber-700' => in_array($item['status'] ?? '', ['Pending review', 'Ready for review', 'Submitted', 'In progress', 'Pending'], true),
                    'bg-red-50 text-red-700' => ($item['status'] ?? '') === 'Needs revision',
                    'bg-slate-100 text-slate-600' => ($item['status'] ?? '') === 'Not started',
                    'bg-gw-accent-soft text-gw-accent-dark' => ! in_array($item['status'] ?? '', ['Completed', 'Pending review', 'Ready for review', 'Submitted', 'In progress', 'Pending', 'Needs revision', 'Not started'], true),
                ])>{{ $item['status'] }}</span>
            </li>
        @empty
            <li class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                <p class="text-sm font-semibold text-gw-primary">No checklist items yet</p>
                <p class="mt-1 text-xs text-slate-500">Onboarding steps will appear here once they are configured for your account.</p>
            </li>
        @endforelse
    </ul>
</section>
