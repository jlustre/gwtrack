<section @class([
    'space-y-6' => ! ($embedded ?? false),
    'flex min-h-full flex-1 flex-col gap-4 p-4' => ($embedded ?? false),
])>
        @if ($embedded ?? false)
        <div @class([
            'flex flex-col gap-4 rounded-lg border border-slate-200 bg-white shadow-sm lg:flex-row lg:items-center lg:justify-between',
            'shrink-0 p-4' => ($embedded ?? false),
        ])>
            <div @class(['min-w-0' => ($embedded ?? false)])>
                <h1 class="sr-only">{{ $config['label'] }}</h1>
            </div>
            @if ($canManage)
                <div class="flex shrink-0 flex-nowrap items-center gap-2">
                    <a href="{{ route('admin.management.create', array_merge([$resource], $indexQueryParams ?? [])) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        {{ ($canUpdateSeeder && $resource === 'checklists') ? 'Add Item' : 'Add Record' }}
                    </a>
                    @if ($canUpdateSeeder)
                        <form method="POST" class="inline-flex" action="{{ route('admin.management.update-seeder', array_filter([
                            $resource,
                            'search' => $filters['search'] ?? null,
                            'trashed' => $filters['trashed'] ?? null,
                            'checklist_type' => $filters['checklist_type'] ?? null,
                            'active' => $filters['active'] ?? null,
                        ])) }}">
                            @csrf
                            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-gw-accent bg-white px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/10">
                                Update Seeder
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
        @else
        <x-admin.page-hero
            eyebrow="Admin Management"
            :title="$config['label']"
            :description="$config['description']"
        >
            @if ($canManage)
                <x-slot:actions>
                    <a href="{{ route('admin.management.create', array_merge([$resource], $indexQueryParams ?? [])) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                        {{ ($canUpdateSeeder && $resource === 'checklists') ? 'Add Item' : 'Add Record' }}
                    </a>
                    @if ($canUpdateSeeder)
                        <form method="POST" class="inline-flex" action="{{ route('admin.management.update-seeder', array_filter([
                            $resource,
                            'search' => $filters['search'] ?? null,
                            'trashed' => $filters['trashed'] ?? null,
                            'checklist_type' => $filters['checklist_type'] ?? null,
                            'active' => $filters['active'] ?? null,
                        ])) }}">
                            @csrf
                            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                                Update Seeder
                            </button>
                        </form>
                    @endif
                </x-slot:actions>
            @endif
        </x-admin.page-hero>
        @endif

        @if (session('status'))
            <div @class([
                'rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800',
                'shrink-0' => ($embedded ?? false),
            ])>
                {{ str(session('status'))->replace('-', ' ')->title() }}
            </div>
        @endif

        @if (session('error'))
            <div @class([
                'rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900',
                'shrink-0' => ($embedded ?? false),
            ])>
                {{ session('error') }}
            </div>
        @endif

        @if ($resource === 'email-templates')
            @include('admin.management.partials.email-template-tokens')
        @endif

        <form method="GET" @class([
            'flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm lg:flex-nowrap',
            'shrink-0' => ($embedded ?? false),
        ])>
            @if ($embedded ?? false)
                <input type="hidden" name="embedded" value="1">
            @endif
            <input
                name="search"
                value="{{ $filters['search'] }}"
                placeholder="Search {{ strtolower($config['label']) }}"
                class="min-w-[10rem] flex-1 basis-48 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
            @if ($resource === 'resources')
                <select name="category" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="" @selected(($filters['category'] ?? '') === '')>All categories</option>
                    @foreach (\App\Support\ResourceDocumentCategories::optionsForSelect() as $categoryKey => $categoryLabel)
                        <option value="{{ $categoryKey }}" @selected(($filters['category'] ?? '') === $categoryKey)>{{ $categoryLabel }}</option>
                    @endforeach
                </select>
            @endif
            @if ($resource === 'checklists')
                <select name="checklist_type" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="" @selected(($filters['checklist_type'] ?? '') === '')>All types</option>
                    @foreach ($checklistTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) ($filters['checklist_type'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            @endif
            @if (collect($config['fields'])->contains(fn ($field) => $field['name'] === 'is_active'))
                <select name="active" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="" @selected(($filters['active'] ?? '') === '')>All statuses</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Active</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactive</option>
                </select>
            @endif
            @if ($config['sortable'] ?? false)
                <select name="sort" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach (($config['sort_columns'] ?? ['sort_order', 'name']) as $sortColumn)
                        <option value="{{ $sortColumn }}" @selected(($filters['sort'] ?: ($config['order_by'] ?? 'sort_order')) === $sortColumn)>
                            Sort by {{ str($sortColumn)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
                <select name="direction" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="asc" @selected(($filters['direction'] ?: ($config['order_direction'] ?? 'asc')) === 'asc')>Ascending</option>
                    <option value="desc" @selected(($filters['direction'] ?: ($config['order_direction'] ?? 'asc')) === 'desc')>Descending</option>
                </select>
            @endif
            <select name="trashed" class="shrink-0 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="" @selected($filters['trashed'] === '')>Not archived</option>
                <option value="with" @selected($filters['trashed'] === 'with')>With archived</option>
                <option value="only" @selected($filters['trashed'] === 'only')>Archived only</option>
            </select>
            <button class="shrink-0 rounded-md bg-gw-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-[#13345f]">Filter</button>
        </form>

        @if ($resource === 'checklists')
            @include('admin.management.partials.checklist-type-tabs', [
                'filters' => $filters,
                'checklistTypes' => $checklistTypes,
                'checklistTypeQuickFilters' => $checklistTypeQuickFilters ?? null,
                'embedded' => $embedded ?? false,
            ])
        @endif

        @if ($resource === 'resources')
            <div @class([
                'overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm',
                'shrink-0' => ($embedded ?? false),
            ])>
                <div class="border-b border-gw-accent/20 bg-gw-accent/5 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gw-primary">My Favorites</h2>
                    <p class="mt-1 text-xs text-slate-600">Documents you have starred for quick access.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Published</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($favoriteRecords as $favorite)
                                <tr>
                                    <td class="max-w-xs px-4 py-3 text-slate-700">
                                        <span class="line-clamp-2">{{ $favorite->title ?: 'N/A' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $favorite->category ?: 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full {{ $favorite->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2 py-1 text-xs font-semibold">
                                            {{ $favorite->is_published ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full {{ $favorite->trashed() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2 py-1 text-xs font-semibold">
                                            {{ $favorite->trashed() ? 'Archived' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5">
                                            @include('admin.management.partials.resource-favorite-button', [
                                                'recordId' => $favorite->id,
                                                'isFavorited' => true,
                                                'filters' => $filters,
                                                'embedded' => $embedded ?? false,
                                            ])
                                            @if (($favorite->type ?? 'document') === 'document')
                                                @include('admin.management.partials.resource-document-access-actions', [
                                                    'record' => $favorite,
                                                ])
                                            @endif
                                            @if ($canManage)
                                                <a
                                                    href="{{ route('admin.management.edit', ['resources', $favorite->id]) }}"
                                                    title="Edit"
                                                    aria-label="Edit record"
                                                    class="group gw-icon-btn"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                    <span class="sr-only">Edit</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No favorites yet. Star a document in the list below to add it here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div @class([
            'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm',
            'flex min-h-0 flex-1 flex-col' => ($embedded ?? false),
        ])>
            @php($booleanColumns = collect($config['fields'])->where('type', 'boolean')->pluck('name')->all())
            @php($hasActiveColumn = collect($config['fields'])->contains(fn ($field) => $field['name'] === 'is_active'))
            @php($useInlineModals = $config['use_inline_modals'] ?? true)
            <div @class([
                'overflow-x-auto',
                'min-h-0 flex-1 overflow-y-auto' => ($embedded ?? false),
            ])>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            @foreach ($config['columns'] as $column)
                                <th class="px-4 py-3">{{ match ($column) {
                                    'prerequisites_label' => 'Prerequisites',
                                    'requires_confirmation' => 'Confirmation Needed',
                                    default => str($column)->replace('_', ' ')->title(),
                                } }}</th>
                            @endforeach
                            <th class="px-4 py-3">Status</th>
                            <th class="px-2 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            @php($isUploadedPdf = ($resource === 'resources') ? \App\Models\PortalResource::isUploadedPdfAttributes($record->file_path ?? null, $record->file_format ?? null, $record->content ?? null) : false)
                            @php($isRecordActive = (int) ($record->is_active ?? 0) === 1)
                            @php($canEditRecord = $resource !== 'resources' || auth()->user()->canUpdateDocument($record))
                            <tr @if ($useInlineModals) x-data="{ viewOpen: false, editOpen: false }" @endif>
                                @foreach ($config['columns'] as $column)
                                    @php($value = data_get($record, $column))
                                    <td class="max-w-xs px-4 py-3 text-slate-700">
                                        @if ($column === 'requires_confirmation')
                                            <span class="rounded-full {{ (bool) $value ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-700' }} px-2 py-1 text-xs font-semibold">
                                                {{ (bool) $value ? 'Needed' : 'Auto-approved' }}
                                            </span>
                                        @elseif (in_array($column, $booleanColumns, true))
                                            <span class="rounded-full {{ (bool) $value ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2 py-1 text-xs font-semibold">
                                                {{ (bool) $value ? 'Yes' : 'No' }}
                                            </span>
                                        @elseif ($column === 'country')
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                {{ $value ?: 'Global' }}
                                            </span>
                                        @elseif ($column === 'checklist_type_id' && $resource === 'checklists')
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                {{ $checklistTypes->firstWhere('id', $value)?->name ?? 'N/A' }}
                                            </span>
                                        @elseif ($column === 'task_category_id' && in_array($resource, ['tasks', 'task-users'], true))
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                                {{ $taskCategories->firstWhere('id', $value)?->name ?? 'N/A' }}
                                            </span>
                                        @elseif ($column === 'task_id' && $resource === 'task-users')
                                            <span class="line-clamp-2">{{ $libraryTasks->firstWhere('id', $value)?->title ?? 'N/A' }}</span>
                                        @elseif (in_array($column, ['assignee_id', 'assignor_id'], true) && $resource === 'task-users')
                                            @php($member = $memberUsers->firstWhere('id', $value))
                                            <span class="line-clamp-2">{{ $member ? $member->name.' ('.$member->email.')' : 'N/A' }}</span>
                                        @elseif ($column === 'prerequisites_label')
                                            <span class="line-clamp-2">{{ $value ?: '—' }}</span>
                                        @elseif ($column === 'title' && filled(data_get($record, 'description')))
                                            <span class="flex min-w-0 items-center gap-1.5">
                                                <span class="line-clamp-2">{{ $value ?: 'N/A' }}</span>
                                                <x-checklist-description-help :text="data_get($record, 'description')" />
                                            </span>
                                        @else
                                            <span class="line-clamp-2">{{ $value ?: 'N/A' }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <span class="rounded-full {{
                                        $record->deleted_at
                                            ? 'bg-amber-50 text-amber-700'
                                            : (($hasActiveColumn && ! $isRecordActive) ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700')
                                    }} px-2 py-1 text-xs font-semibold">
                                        {{ $record->deleted_at ? 'Archived' : (($hasActiveColumn && ! $isRecordActive) ? 'Inactive' : 'Active') }}
                                    </span>
                                </td>
                                <td class="px-2 py-3">
                                    <div class="flex justify-end gap-0.5">
                                        @if ($resource === 'resources' && ($record->type ?? 'document') === 'document')
                                            @include('admin.management.partials.resource-favorite-button', [
                                                'recordId' => $record->id,
                                                'isFavorited' => in_array($record->id, $favoriteResourceIds ?? [], true),
                                                'filters' => $filters,
                                                'embedded' => $embedded ?? false,
                                            ])
                                        @elseif (! $isUploadedPdf)
                                            <a
                                                href="{{ route('admin.management.show', array_merge([$resource, $record->id], $indexQueryParams ?? [])) }}"
                                                @if ($useInlineModals)
                                                    x-on:click.prevent="{{ ($canManage && $canEditRecord) ? 'editOpen = true' : 'viewOpen = true' }}"
                                                @endif
                                                title="{{ ($useInlineModals && $canManage && $canEditRecord) ? 'View / Edit' : 'View' }}"
                                                aria-label="{{ ($useInlineModals && $canManage && $canEditRecord) ? 'View or edit record' : 'View record' }}"
                                                class="group gw-icon-btn"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                <span class="sr-only">View</span>
                                                <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">View</span>
                                            </a>
                                        @endif
                                        @if ($resource === 'resources' && ($record->type ?? 'document') === 'document')
                                            @include('admin.management.partials.resource-document-access-actions', [
                                                'record' => $record,
                                                'showView' => false,
                                            ])
                                        @endif
                                        @if ($canManage)
                                            @if (($config['sortable'] ?? false) && ! $record->deleted_at)
                                                @include('admin.management.partials.resource-sort-actions', [
                                                    'resource' => $resource,
                                                    'recordId' => $record->id,
                                                    'indexQueryParams' => $indexQueryParams ?? [],
                                                ])
                                            @endif
                                            @if ($canEditRecord)
                                                <a
                                                    href="{{ route('admin.management.edit', array_merge([$resource, $record->id], $indexQueryParams ?? [])) }}"
                                                    @if ($useInlineModals) x-on:click.prevent="editOpen = true" @endif
                                                    title="Edit"
                                                    aria-label="Edit record"
                                                    class="group gw-icon-btn"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                    <span class="sr-only">Edit</span>
                                                    <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Edit</span>
                                                </a>
                                            @elseif ($resource === 'resources')
                                                <button
                                                    type="button"
                                                    title="Read only"
                                                    aria-label="Cannot edit record you do not own"
                                                    class="group gw-icon-btn-warning"
                                                    x-on:click="alert('You can only update documents that you created. Contact an administrator if this record needs changes.')"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                        <path d="m2 2 20 20" />
                                                    </svg>
                                                    <span class="sr-only">Cannot edit</span>
                                                    <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Owner only</span>
                                                </button>
                                            @endif
                                            @if ($hasActiveColumn && ! $record->deleted_at)
                                                <form method="POST" action="{{ route('admin.management.status', array_merge([$resource, $record->id], $indexQueryParams ?? [])) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    @php($statusLabel = $isRecordActive ? 'Deactivate' : 'Activate')
                                                    <button
                                                        title="{{ $statusLabel }}"
                                                        aria-label="{{ $statusLabel }} record"
                                                        class="group {{ $isRecordActive ? 'gw-icon-btn-warning' : 'gw-icon-btn-success' }}"
                                                    >
                                                        @if ($isRecordActive)
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                <circle cx="12" cy="12" r="10" />
                                                                <path d="m4.9 4.9 14.2 14.2" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                <path d="M20 6 9 17l-5-5" />
                                                            </svg>
                                                        @endif
                                                        <span class="sr-only">{{ $statusLabel }}</span>
                                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">{{ $statusLabel }}</span>
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($record->deleted_at)
                                                @if ($canDeleteRecords)
                                                <form method="POST" action="{{ route('admin.management.restore', [$resource, $record->id]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        title="Restore"
                                                        aria-label="Restore record"
                                                        class="group gw-icon-btn-success"
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path d="M3 12a9 9 0 1 0 3-6.7" />
                                                            <path d="M3 4v6h6" />
                                                        </svg>
                                                        <span class="sr-only">Restore</span>
                                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Restore</span>
                                                    </button>
                                                </form>
                                                @endif
                                            @else
                                                @if ($canDeleteRecords)
                                                <form method="POST" action="{{ route('admin.management.destroy', [$resource, $record->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        title="Archive"
                                                        aria-label="Archive record"
                                                        class="group gw-icon-btn"
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <rect x="3" y="4" width="18" height="4" rx="1" />
                                                            <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8" />
                                                            <path d="M10 12h4" />
                                                        </svg>
                                                        <span class="sr-only">Archive</span>
                                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Archive</span>
                                                    </button>
                                                </form>
                                                @endif
                                            @endif
                                        @endif
                                    </div>

                                    @if ($useInlineModals)
                                    <div
                                        x-show="viewOpen"
                                        x-cloak
                                        x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                                        role="dialog"
                                        aria-modal="true"
                                        aria-labelledby="view-record-title-{{ $record->id }}"
                                        x-on:keydown.escape.window="viewOpen = false"
                                    >
                                        <div class="absolute inset-0" x-on:click="viewOpen = false"></div>
                                        <div class="relative max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-2xl">
                                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">View Record</p>
                                                    <h2 id="view-record-title-{{ $record->id }}" class="mt-1 text-xl font-semibold text-gw-primary">{{ data_get($record, 'title') ?? data_get($record, 'name') ?? data_get($record, 'code') ?? 'Record Details' }}</h2>
                                                </div>
                                                <button type="button" x-on:click="viewOpen = false" class="gw-icon-btn-close" aria-label="Close view modal">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M18 6 6 18" />
                                                        <path d="m6 6 12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="px-6 py-5">
                                                <dl class="grid gap-4 md:grid-cols-2">
                                                    @foreach ($config['fields'] as $field)
                                                        @php($viewValue = data_get($record, $field['name']))
                                                        <div class="{{ $field['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                                                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $field['label'] }}</dt>
                                                            <dd class="mt-2 rounded-md bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-800">
                                                                @if ($field['name'] === 'requires_confirmation')
                                                                    <span class="rounded-full {{ (bool) $viewValue ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-700' }} px-2 py-1 text-xs font-semibold">
                                                                        {{ (bool) $viewValue ? 'Needed' : 'Auto-approved' }}
                                                                    </span>
                                                                @elseif ($field['type'] === 'boolean')
                                                                    <span class="rounded-full {{ (bool) $viewValue ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-700' }} px-2 py-1 text-xs font-semibold">
                                                                        {{ (bool) $viewValue ? 'Yes' : 'No' }}
                                                                    </span>
                                                                @elseif ($field['name'] === 'checklist_type_id' && $resource === 'checklists')
                                                                    {{ $checklistTypes->firstWhere('id', $viewValue)?->name ?? 'N/A' }}
                                                                @elseif ($field['name'] === 'country')
                                                                    {{ $viewValue ?: 'Global - all countries' }}
                                                                @else
                                                                    {{ $viewValue ?: 'N/A' }}
                                                                @endif
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            </div>
                                            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                                <button type="button" x-on:click="viewOpen = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
                                                @if ($canEditRecord ?? true)
                                                    <button type="button" x-on:click="viewOpen = false; editOpen = true" class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Edit Item</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    @if ($useInlineModals && $canManage && ($resource !== 'resources' || auth()->user()->canUpdateDocument($record)))
                                        <div
                                            x-show="editOpen"
                                            x-cloak
                                            x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-labelledby="edit-record-title-{{ $record->id }}"
                                            x-on:keydown.escape.window="editOpen = false"
                                        >
                                            <div class="absolute inset-0" x-on:click="editOpen = false"></div>
                                            <div class="relative max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-2xl">
                                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Edit Record</p>
                                                        <h2 id="edit-record-title-{{ $record->id }}" class="mt-1 text-xl font-semibold text-gw-primary">{{ data_get($record, 'title') ?? data_get($record, 'name') ?? data_get($record, 'code') ?? 'Record Details' }}</h2>
                                                    </div>
                                                    <button type="button" x-on:click="editOpen = false" class="gw-icon-btn-close" aria-label="Close edit modal">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path d="M18 6 6 18" />
                                                            <path d="m6 6 12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.management.update', array_merge([$resource, $record->id], $indexQueryParams ?? [])) }}" class="space-y-5 px-6 py-5">
                                                    @csrf
                                                    @method('PATCH')
                                                    @foreach ($indexQueryParams ?? [] as $queryKey => $queryValue)
                                                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                                    @endforeach
                                                    @php($fieldIdPrefix = 'modal_'.$resource.'_'.$record->id)
                                                    @include('admin.management.partials.form')
                                                    @php($fieldIdPrefix = null)
                                                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                                        <button type="button" x-on:click="editOpen = false" class="inline-flex justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                                        <button class="inline-flex justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['columns']) + 2 }}" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $records->links() }}
            </div>
        </div>
</section>
