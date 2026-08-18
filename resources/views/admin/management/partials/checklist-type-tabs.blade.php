@php
    $quickFilters = $checklistTypeQuickFilters ?? [
        ['code' => 'onboarding', 'label' => 'Onboarding'],
        ['code' => 'fap', 'label' => 'FAP'],
        ['code' => 'cfm-mentoring', 'label' => 'CFM Mentoring'],
        ['code' => 'cfm-training', 'label' => 'CFM Training'],
    ];

    $preservedQuery = array_filter([
        'search' => $filters['search'] ?? null,
        'trashed' => $filters['trashed'] ?? null,
        'active' => $filters['active'] ?? null,
        'sort' => $filters['sort'] ?? null,
        'direction' => $filters['direction'] ?? null,
        'embedded' => ($embedded ?? false) ? '1' : null,
    ], fn ($value) => filled($value));

    $selectedTypeId = (string) ($filters['checklist_type'] ?? '');
@endphp

<div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gw-primary">Checklist Type</h2>
            <p class="mt-0.5 text-xs text-slate-500">Select a type to filter the checklist items below. Your selection stays active until you choose another.</p>
        </div>
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Checklist type filters">
            @foreach ($quickFilters as $quickFilter)
                @php
                    $type = $checklistTypes->firstWhere('code', $quickFilter['code']);
                    $typeId = $type ? (string) $type->id : null;
                    $isActive = $typeId !== null && $selectedTypeId === $typeId;
                @endphp
                @if ($typeId)
                    <a
                        href="{{ route('admin.management.resource.index', array_merge(['checklists'], $preservedQuery, ['checklist_type' => $typeId])) }}"
                        role="tab"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        @class([
                            'inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition',
                            'border-gw-accent bg-gw-accent text-gw-primary shadow-sm' => $isActive,
                            'border-slate-300 bg-white text-slate-700 hover:border-gw-accent/60 hover:bg-gw-accent-soft' => ! $isActive,
                        ])
                    >
                        {{ $quickFilter['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    @if ($selectedTypeId !== '')
        @php($activeType = $checklistTypes->firstWhere('id', (int) $selectedTypeId))
        <p class="mt-3 text-xs font-medium text-slate-600">
            Showing <span class="font-semibold text-gw-primary">{{ $activeType?->name ?? 'selected type' }}</span> checklist items.
        </p>
    @endif
</div>
