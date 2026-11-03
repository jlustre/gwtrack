<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistType;
use App\Models\EmailTemplateToken;
use App\Models\PortalResource;
use App\Models\ProfileCompletionField;
use App\Models\User;
use App\Rules\UrlOrRelativePath;
use App\Services\DocumentLinkSyncService;
use App\Services\ResourcePdfService;
use App\Support\ResourceDocumentCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminManagementController extends Controller
{
    public function __construct(
        private readonly ResourcePdfService $resourcePdf,
        private readonly DocumentLinkSyncService $documentLinkSync,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->canViewManagementIndex(), 403);

        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $page = max(1, $request->integer('page', 1));
        $perPage = 12;

        $items = collect($this->resources())
            ->map(function (array $config, string $key): array {
                $counts = $this->resourceCounts($config, $key);

                return [
                    'key' => $key,
                    'label' => $config['label'],
                    'table' => $config['table'],
                    'description' => $config['description'],
                    'category' => $config['category'] ?? $this->resourceCategoryFor($key),
                    'record_count' => $counts['record_count'],
                    'archived_count' => $counts['archived_count'],
                ];
            })
            ->filter(fn (array $resource): bool => $this->canViewResource($resource['key']))
            ->when($search !== '', function ($collection) use ($search) {
                $needle = Str::lower($search);

                return $collection->filter(function (array $resource) use ($needle): bool {
                    $haystack = Str::lower(implode(' ', [
                        $resource['key'],
                        $resource['label'],
                        $resource['table'],
                        $resource['description'],
                    ]));

                    return Str::contains($haystack, $needle);
                });
            })
            ->when($category !== '', fn ($collection) => $collection->where('category', $category))
            ->sortBy('label')
            ->values();

        $resources = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => route('admin.management.index'),
                'query' => $request->query(),
            ],
        );

        return view('admin.management.index', [
            'resources' => $resources,
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'categories' => $this->resourceCategories(),
        ]);
    }

    public function resourceIndex(Request $request, string $resource): View
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canViewResource($resource), 403);

        $search = $request->string('search')->toString();
        $trashed = $request->string('trashed')->toString();
        $category = $request->string('category')->toString();
        $checklistType = $request->string('checklist_type')->toString();
        $active = $request->string('active')->toString();

        $checklistTypes = in_array($resource, ['checklists', 'checklist-types'], true)
            ? ChecklistType::query()
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name'])
            : collect();

        $checklistTypeQuickFilters = $resource === 'checklists'
            ? $this->checklistTypeQuickFilters()
            : [];

        $validChecklistTypeIds = $checklistTypes->pluck('id')->map(fn (int $id): string => (string) $id)->all();

        $hasSoftDeletes = $this->hasColumn($config['table'], 'deleted_at');

        $records = DB::table($config['table'])
            ->when($resource === 'resources', fn ($query) => $query->where('type', 'document'))
            ->when($hasSoftDeletes && $trashed === 'only', fn ($query) => $query->whereNotNull('deleted_at'))
            ->when($hasSoftDeletes && $trashed !== 'only' && $trashed !== 'with', fn ($query) => $query->whereNull('deleted_at'))
            ->when(
                $resource === 'resources' && $category !== '' && ResourceDocumentCategories::isValid($category),
                fn ($query) => $query->where('category', $category),
            )
            ->when(
                $resource === 'checklists' && $checklistType !== '' && in_array($checklistType, $validChecklistTypeIds, true),
                fn ($query) => $query->where('checklist_type_id', (int) $checklistType),
            )
            ->when($search, function ($query) use ($config, $search): void {
                $query->where(function ($query) use ($config, $search): void {
                    foreach ($config['search'] as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when(
                $active === '1' && $this->hasColumn($config['table'], 'is_active'),
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $active === '0' && $this->hasColumn($config['table'], 'is_active'),
                fn ($query) => $query->where('is_active', false),
            )
            ->orderBy(
                $this->resolveResourceOrderBy($request, $config),
                $this->resolveResourceOrderDirection($request, $config),
            )
            ->paginate($resource === 'checklists' ? 25 : 12)
            ->withQueryString();

        if ($resource === 'checklist-types') {
            $this->enrichChecklistTypeRecords($records);
        }

        $favoriteResourceIds = [];
        $favoriteRecords = collect();

        $taskCategories = in_array($resource, ['tasks', 'task-users'], true)
            ? DB::table('task_categories')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name'])
            : collect();

        $libraryTasks = $resource === 'task-users'
            ? DB::table('tasks')->whereNull('deleted_at')->orderBy('title')->get(['id', 'title'])
            : collect();

        $memberUsers = $resource === 'task-users'
            ? DB::table('users')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        if ($resource === 'resources') {
            $favoriteResourceIds = $request->user()
                ->favoritePortalResources()
                ->where('type', 'document')
                ->pluck('resources.id')
                ->all();

            $favoriteRecords = $request->user()
                ->favoritePortalResources()
                ->where('type', 'document')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();
        }

        return view('admin.management.resource-index', [
            'resource' => $resource,
            'config' => $config,
            'records' => $records,
            'filters' => [
                'search' => $search,
                'trashed' => $trashed,
                'category' => $category,
                'checklist_type' => $checklistType,
                'active' => $active,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            'indexQueryParams' => $this->resourceIndexQueryParams($request, $resource),
            'checklistTypes' => $checklistTypes,
            'checklistTypeQuickFilters' => $checklistTypeQuickFilters,
            'embedded' => $request->boolean('embedded'),
            'canManage' => $this->canManageResource($resource),
            'canDeleteRecords' => $this->canDeleteResourceRecords($resource)
                && ! in_array($resource, ['ranks', 'rank-requirements'], true),
            'canUpdateSeeder' => $this->canUpdateSeeder($resource),
            'options' => ($config['use_inline_modals'] ?? true) ? $this->formOptionsFor($config) : [],
            'favoriteResourceIds' => $favoriteResourceIds,
            'favoriteRecords' => $favoriteRecords,
            'taskCategories' => $taskCategories,
            'libraryTasks' => $libraryTasks,
            'memberUsers' => $memberUsers,
        ]);
    }

    public function create(Request $request, string $resource): View
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);

        return view('admin.management.create', [
            'resource' => $resource,
            'config' => $config,
            'record' => null,
            'options' => $this->formOptionsFor($config),
            'indexQueryParams' => $this->resourceIndexQueryParams($request, $resource),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);

        $validated = $this->validatedData($request, $config);

        if ($resource === 'rank-requirements') {
            $code = substr(Str::slug($validated['title']), 0, 140);
            $code = $code !== '' ? $code : 'custom-'.Str::uuid();
            $exists = DB::table('rank_requirements')
                ->where('rank_id', $validated['rank_id'])
                ->where('code', $code)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'title' => 'A requirement with this title already exists for this rank.',
                ]);
            }

            $validated['code'] = $code;
        }

        if ($resource === 'checklist-types') {
            $validated = $this->normalizeChecklistTypeData($validated);
        }

        if ($resource === 'email-template-tokens') {
            $validated = $this->normalizeEmailTemplateTokenData($validated);
        }

        if ($resource === 'email-templates') {
            $validated['token_values'] = $this->encodeEmailTemplateTokenValues($request);
        }

        if ($resource === 'notifications') {
            $validated = $this->normalizeNotificationStoreData($validated);
        }

        if ($resource === 'tasks') {
            $validated = $this->normalizeTaskData($validated);
        }

        if ($resource === 'task-users') {
            $validated = $this->normalizeTaskUserData($validated);
        }

        $validated = $this->normalizeJsonFieldValues($validated, $config);

        if (array_key_exists('slug', $validated) && blank($validated['slug']) && isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if (($config['uses_creator'] ?? false) && ! array_key_exists('created_by', $validated)) {
            $validated['created_by'] = $request->user()->id;
        }

        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        if ($config['uuid_primary'] ?? false) {
            $id = (string) Str::uuid();
            $validated['id'] = $id;
            DB::table($config['table'])->insert($validated);

            return redirect()
                ->route('admin.management.resource.index', [$resource])
                ->with('status', 'record-created');
        }

        $id = DB::table($config['table'])->insertGetId($validated);

        if ($resource === 'checklist-types') {
            $this->syncChecklistTypePrerequisites(
                $id,
                $this->validatedChecklistTypePrerequisites($request),
            );
        }

        $pdfStatus = $this->syncResourceDocumentFile($request, $resource, $id, (bool) $request->boolean('generate_pdf'));
        $this->syncDocumentLinks($resource, $id);

        return $this->redirectAfterResourceSave(
            $request,
            $resource,
            $id,
            $pdfStatus ?: 'record-created',
        );
    }

    public function show(string $resource, string|int $record): View
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canViewResource($resource), 403);

        $row = DB::table($config['table'])->where('id', $record)->firstOrFail();

        return view('admin.management.show', [
            'resource' => $resource,
            'config' => $config,
            'record' => $row,
            'canManage' => $this->canManageResource($resource),
            'canDeleteRecord' => $this->canDeleteResourceRecords($resource)
                && ! in_array($resource, ['ranks', 'rank-requirements'], true),
        ]);
    }

    public function edit(Request $request, string $resource, string|int $record): View
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);

        $row = DB::table($config['table'])->where('id', $record)->firstOrFail();

        if ($resource === 'checklist-types') {
            $row->prerequisite_checklist_type_ids = $this->checklistTypePrerequisiteIds($record);
        }

        return view('admin.management.edit', [
            'resource' => $resource,
            'config' => $config,
            'record' => $row,
            'options' => $this->formOptionsFor($config),
            'canUpdateRecord' => $this->canUpdateResourceRecord($resource, $row),
            'canDeleteRecord' => $this->canDeleteResourceRecords($resource)
                && ! in_array($resource, ['ranks', 'rank-requirements'], true),
            'indexQueryParams' => $this->resourceIndexQueryParams($request, $resource),
        ]);
    }

    public function update(Request $request, string $resource, string|int $record): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);

        $existing = DB::table($config['table'])->where('id', $record)->firstOrFail();

        if ($response = $this->denyPortalResourceUpdate($resource, $existing)) {
            return $response;
        }

        if ($resource === 'ranks') {
            abort_unless($request->input('code') === $existing->code, 422, 'Rank codes cannot be changed after creation.');
            abort_if(
                $existing->code === config('greatway-compensation.entry_rank', 'TA')
                    && ! $request->boolean('is_active'),
                422,
                'The entry rank must remain active.',
            );
        }

        $validated = $this->validatedData($request, $config, $record);

        if ($resource === 'checklist-types') {
            $validated = $this->normalizeChecklistTypeData($validated);
        }

        if ($resource === 'email-template-tokens') {
            $validated = $this->normalizeEmailTemplateTokenData($validated);
        }

        if ($resource === 'email-templates') {
            $validated['token_values'] = $this->encodeEmailTemplateTokenValues($request);
        }

        if ($resource === 'notifications') {
            $validated = $this->normalizeNotificationStoreData($validated);
        }

        if ($resource === 'tasks') {
            $validated = $this->normalizeTaskData($validated);
        }

        if ($resource === 'task-users') {
            $validated = $this->normalizeTaskUserData($validated);
        }

        $validated = $this->normalizeJsonFieldValues($validated, $config);

        if (array_key_exists('slug', $validated) && blank($validated['slug']) && isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['updated_at'] = now();

        DB::table($config['table'])->where('id', $record)->update($validated);

        if ($resource === 'checklist-types') {
            $this->syncChecklistTypePrerequisites(
                $record,
                $this->validatedChecklistTypePrerequisites($request, $record),
            );
        }

        $pdfStatus = $this->syncResourceDocumentFile(
            $request,
            $resource,
            $record,
            (bool) $request->boolean('generate_pdf'),
        );
        $this->syncDocumentLinks($resource, $record);

        return $this->redirectAfterResourceSave(
            $request,
            $resource,
            $record,
            $pdfStatus ?: 'record-updated',
        );
    }

    public function generateResourcePdf(int $record): RedirectResponse
    {
        abort_unless($this->canManageResource('resources'), 403);

        $portalResource = PortalResource::query()->findOrFail($record);

        if ($response = $this->denyPortalResourceUpdate('resources', $portalResource)) {
            return $response;
        }

        $this->resourcePdf->generate($portalResource);

        $this->documentLinkSync->syncAll();

        return redirect()
            ->route('admin.management.edit', ['resources', $record])
            ->with('status', 'resource-pdf-generated');
    }

    public function viewResourcePdf(int $record): BinaryFileResponse
    {
        abort_unless($this->canViewResource('resources'), 403);

        $portalResource = PortalResource::query()->findOrFail($record);
        abort_unless($portalResource->hasDownloadableFile() && $portalResource->hasPdfPreview(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($portalResource->file_path), 404);

        $filename = basename($portalResource->file_path) ?: str($portalResource->title)->slug().'.pdf';

        return response()->file(
            $disk->path($portalResource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    public function toggleResourceFavorite(Request $request, int $record): RedirectResponse
    {
        abort_unless($this->canViewResource('resources'), 403);

        $portalResource = PortalResource::query()->findOrFail($record);
        abort_unless($portalResource->type === 'document', 404);

        $attached = $request->user()->favoritePortalResources()->toggle($portalResource->id);

        $status = $attached['attached'] === [] ? 'favorite-removed' : 'favorite-added';

        return redirect()
            ->route('admin.management.resource.index', array_filter([
                'resources',
                'search' => $request->string('search')->toString() ?: null,
                'trashed' => $request->string('trashed')->toString() ?: null,
                'category' => $request->string('category')->toString() ?: null,
                'embedded' => $request->boolean('embedded') ? '1' : null,
            ]))
            ->with('status', $status);
    }

    public function toggleStatus(Request $request, string $resource, string|int $record): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);
        abort_unless($this->hasColumn($config['table'], 'is_active'), 404);

        $row = DB::table($config['table'])->where('id', $record)->firstOrFail();
        abort_if(
            $resource === 'ranks'
                && $row->code === config('greatway-compensation.entry_rank', 'TA')
                && $row->is_active,
            422,
            'The entry rank must remain active.',
        );

        DB::table($config['table'])->where('id', $record)->update([
            'is_active' => ! (bool) $row->is_active,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.management.resource.index', array_merge([$resource], $this->resourceIndexQueryParams($request, $resource)))
            ->with('status', (bool) $row->is_active ? 'record-deactivated' : 'record-activated');
    }

    public function updateSeeder(Request $request, string $resource): RedirectResponse
    {
        $this->resourceConfig($resource);
        abort_unless($this->canUpdateSeeder($resource), 403);

        match ($resource) {
            'email-templates' => File::put($this->seederPath($resource), $this->buildEmailTemplateSeeder()),
            'email-template-tokens' => File::put($this->seederPath($resource), $this->buildEmailTemplateTokenSeeder()),
            'checklists' => $this->writeChecklistSeeders($request),
            'checklist-types' => File::put($this->seederPath($resource), $this->buildChecklistTypeSeeder()),
            'task-categories' => File::put($this->seederPath($resource), $this->buildTaskCategorySeeder()),
            'tasks' => File::put($this->seederPath($resource), $this->buildTaskSeeder()),
            'daily-quotes' => File::put($this->seederPath($resource), $this->buildDailyQuoteSeeder()),
        };

        return redirect()
            ->route('admin.management.resource.index', array_merge([$resource], $this->resourceIndexQueryParams($request, $resource)))
            ->with('status', 'seeder-updated');
    }

    public function reorder(Request $request, string $resource, string|int $record): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);
        abort_unless($config['sortable'] ?? false, 404);
        abort_unless($this->hasColumn($config['table'], 'sort_order'), 404);

        $move = $request->string('move')->toString();
        abort_unless(in_array($move, ['up', 'down'], true), 422);

        $records = DB::table($config['table'])
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        $index = $records->search(fn (object $row): bool => (int) $row->id === (int) $record);
        abort_if($index === false, 404);

        $neighborIndex = $move === 'up' ? $index - 1 : $index + 1;
        if ($neighborIndex < 0 || $neighborIndex >= $records->count()) {
            return redirect()
                ->route('admin.management.resource.index', array_merge([$resource], $this->resourceIndexQueryParams($request, $resource)))
                ->with('status', 'record-order-unchanged');
        }

        $current = $records[$index];
        $neighbor = $records[$neighborIndex];

        DB::table($config['table'])->where('id', $current->id)->update([
            'sort_order' => $neighbor->sort_order,
            'updated_at' => now(),
        ]);

        DB::table($config['table'])->where('id', $neighbor->id)->update([
            'sort_order' => $current->sort_order,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.management.resource.index', array_merge([$resource], $this->resourceIndexQueryParams($request, $resource)))
            ->with('status', 'record-order-updated');
    }

    public function destroy(string $resource, string|int $record): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);
        abort_unless($this->canDeleteResourceRecords($resource), 403);
        abort_if(
            in_array($resource, ['ranks', 'rank-requirements'], true),
            422,
            'Ranks and advancement requirements cannot be deleted. Retire them to preserve member records.',
        );

        DB::table($config['table'])->where('id', $record)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.management.resource.index', [$resource, 'trashed' => 'with'])
            ->with('status', 'record-archived');
    }

    public function restore(Request $request, string $resource, string|int $record): RedirectResponse
    {
        $config = $this->resourceConfig($resource);
        abort_unless($this->canManageResource($resource), 403);
        abort_unless($this->canDeleteResourceRecords($resource), 403);

        DB::table($config['table'])->where('id', $record)->update([
            'deleted_at' => null,
            'updated_at' => now(),
        ]);

        return $this->redirectAfterResourceSave($request, $resource, $record, 'record-restored');
    }

    private function validatedData(Request $request, array $config, string|int|null $record = null): array
    {
        $rules = [];

        foreach ($config['fields'] as $field) {
            if ($field['virtual'] ?? false) {
                continue;
            }

            $fieldRules = $field['rules'];

            if (($field['unique'] ?? false) === true) {
                $fieldRules[] = Rule::unique($config['table'], $field['name'])->ignore($record);
            }

            $rules[$field['name']] = $fieldRules;
        }

        return $request->validate($rules);
    }

    private function normalizeChecklistTypeData(array $validated): array
    {
        if (array_key_exists('max_complete_days', $validated)) {
            $validated['max_complete_days'] = filled($validated['max_complete_days'])
                ? (int) $validated['max_complete_days']
                : null;
        }

        return $validated;
    }

    /**
     * @return array<int, int>
     */
    private function validatedChecklistTypePrerequisites(Request $request, ?int $recordId = null): array
    {
        $validated = $request->validate([
            'prerequisite_checklist_type_ids' => ['nullable', 'array'],
            'prerequisite_checklist_type_ids.*' => ['integer', 'distinct', 'exists:checklist_types,id'],
        ]);

        $ids = collect($validated['prerequisite_checklist_type_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recordId && $ids->contains($recordId)) {
            throw ValidationException::withMessages([
                'prerequisite_checklist_type_ids' => 'A checklist type cannot be its own prerequisite.',
            ]);
        }

        return $ids->all();
    }

    /**
     * @return array<int, int>
     */
    private function checklistTypePrerequisiteIds(int $checklistTypeId): array
    {
        return DB::table('checklist_type_prerequisites')
            ->where('checklist_type_id', $checklistTypeId)
            ->orderBy('prerequisite_checklist_type_id')
            ->pluck('prerequisite_checklist_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $prerequisiteIds
     */
    private function syncChecklistTypePrerequisites(int $checklistTypeId, array $prerequisiteIds): void
    {
        DB::table('checklist_type_prerequisites')
            ->where('checklist_type_id', $checklistTypeId)
            ->delete();

        foreach (array_unique($prerequisiteIds) as $prerequisiteId) {
            if ($prerequisiteId === $checklistTypeId) {
                continue;
            }

            DB::table('checklist_type_prerequisites')->insert([
                'checklist_type_id' => $checklistTypeId,
                'prerequisite_checklist_type_id' => $prerequisiteId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function enrichChecklistTypeRecords(LengthAwarePaginator $records): void
    {
        $typeIds = $records->getCollection()->pluck('id');

        if ($typeIds->isEmpty()) {
            return;
        }

        $prerequisitesByType = DB::table('checklist_type_prerequisites')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklist_type_prerequisites.prerequisite_checklist_type_id')
            ->whereIn('checklist_type_prerequisites.checklist_type_id', $typeIds)
            ->whereNull('checklist_types.deleted_at')
            ->orderBy('checklist_types.sort_order')
            ->orderBy('checklist_types.name')
            ->get([
                'checklist_type_prerequisites.checklist_type_id',
                'checklist_types.name',
            ])
            ->groupBy('checklist_type_id');

        $records->setCollection(
            $records->getCollection()->map(function ($record) use ($prerequisitesByType) {
                $names = $prerequisitesByType
                    ->get($record->id, collect())
                    ->pluck('name')
                    ->values();

                $record->prerequisites_label = $names->isNotEmpty() ? $names->join(', ') : null;

                return $record;
            }),
        );
    }

    private function syncResourceDocumentFile(Request $request, string $resource, int $recordId, bool $forceGenerate): ?string
    {
        if ($resource !== 'resources') {
            return null;
        }

        if ($request->hasFile('pdf_file')) {
            $request->validate([
                'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
                'content_source' => ['nullable', 'in:compose,upload'],
            ]);

            $portalResource = PortalResource::query()->findOrFail($recordId);
            $this->resourcePdf->storeUpload($portalResource, $request->file('pdf_file'));

            return 'resource-pdf-uploaded';
        }

        return $this->syncResourcePdf($resource, $recordId, $forceGenerate);
    }

    private function syncResourcePdf(string $resource, int $recordId, bool $forceGenerate): ?string
    {
        if ($resource !== 'resources') {
            return null;
        }

        if (! $forceGenerate) {
            return null;
        }

        $generated = $this->resourcePdf->generateIfEligible($recordId);

        return $generated ? 'resource-pdf-generated' : null;
    }

    private function syncDocumentLinks(string $resource, int $recordId): void
    {
        if ($resource !== 'resources') {
            return;
        }

        $portalResource = PortalResource::query()->find($recordId);

        if (! $portalResource || ! in_array($portalResource->type, ['document', 'file'], true)) {
            return;
        }

        if (blank($portalResource->content)) {
            return;
        }

        $this->documentLinkSync->syncAll();
    }

    private function resourceConfig(string $resource): array
    {
        $resources = $this->resources();

        abort_unless(isset($resources[$resource]), 404);

        return $this->resolveResourceConfig($resource, $resources[$resource]);
    }

    private function resolveResourceConfig(string $resource, array $config): array
    {
        if ($resource === 'email-templates' && Schema::hasTable('email_template_tokens')) {
            $config['token_reference'] = EmailTemplateToken::activeReference();
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeEmailTemplateTokenData(array $validated): array
    {
        if (array_key_exists('key', $validated)) {
            $validated['key'] = Str::snake(str_replace(['-', ' '], '_', trim((string) $validated['key'])));
        }

        if (array_key_exists('sort_order', $validated)) {
            $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        }

        return $validated;
    }

    private function encodeEmailTemplateTokenValues(Request $request): ?string
    {
        $definitions = collect(EmailTemplateToken::activeReference())->pluck('key');
        $submitted = $request->input('token_values', []);

        if (! is_array($submitted)) {
            return null;
        }

        $values = [];

        foreach ($definitions as $key) {
            if (! array_key_exists($key, $submitted)) {
                continue;
            }

            $value = trim((string) $submitted[$key]);

            if ($value !== '') {
                $values[$key] = $value;
            }
        }

        return $values === [] ? null : json_encode($values);
    }

    private function resourceCounts(array $config, string $key): array
    {
        $table = $config['table'];

        if (! Schema::hasTable($table)) {
            return ['record_count' => 0, 'archived_count' => 0];
        }

        $query = DB::table($table);

        if ($key === 'resources') {
            $query->where('type', 'document');
        }

        $hasSoftDeletes = $this->hasColumn($table, 'deleted_at');

        $recordCount = (clone $query)
            ->when($hasSoftDeletes, fn ($builder) => $builder->whereNull('deleted_at'))
            ->count();

        $archivedCount = $hasSoftDeletes
            ? (clone $query)->whereNotNull('deleted_at')->count()
            : 0;

        return [
            'record_count' => $recordCount,
            'archived_count' => $archivedCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceIndexQueryParams(Request $request, string $resource): array
    {
        $params = array_filter([
            'search' => $request->string('search')->toString() ?: null,
            'trashed' => $request->string('trashed')->toString() ?: null,
            'active' => $request->string('active')->toString() !== '' ? $request->string('active')->toString() : null,
            'sort' => $request->string('sort')->toString() ?: null,
            'direction' => in_array($request->string('direction')->toString(), ['asc', 'desc'], true)
                ? $request->string('direction')->toString()
                : null,
            'embedded' => $request->boolean('embedded') ? '1' : null,
            'page' => $request->input('page'),
        ], fn ($value) => $value !== null && $value !== '');

        if ($resource === 'resources') {
            $category = $request->string('category')->toString();
            if ($category !== '') {
                $params['category'] = $category;
            }
        }

        if ($resource === 'checklists') {
            $checklistType = $request->string('checklist_type')->toString();
            if ($checklistType !== '') {
                $params['checklist_type'] = $checklistType;
            }
        }

        return $params;
    }

    private function redirectAfterResourceSave(
        Request $request,
        string $resource,
        string|int $record,
        string $status,
    ): RedirectResponse {
        if ($resource === 'checklists') {
            return redirect()
                ->route('admin.management.resource.index', array_merge(
                    [$resource],
                    $this->resourceIndexQueryParams($request, $resource),
                ))
                ->with('status', $status);
        }

        return redirect()
            ->route('admin.management.edit', [$resource, $record])
            ->with('status', $status);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    private function checklistTypeQuickFilters(): array
    {
        return [
            ['code' => 'onboarding', 'label' => 'Onboarding'],
            ['code' => 'licensing', 'label' => 'Licensing'],
            ['code' => 'contracting', 'label' => 'Contracting'],
            ['code' => 'fap', 'label' => 'FAP'],
            ['code' => 'cfm-mentoring', 'label' => 'CFM Mentoring'],
            ['code' => 'cfm-training', 'label' => 'CFM Training'],
        ];
    }

    private function resourceCategoryFor(string $key): string
    {
        return match (true) {
            in_array($key, ['ranks', 'rank-requirements', 'badges'], true) => 'rank',
            in_array($key, ['checklists', 'checklist-types', 'checklist-instructions'], true) => 'checklist',
            in_array($key, ['training-categories', 'training-modules', 'training-lessons', 'assessments', 'questions', 'answers'], true) => 'training',
            in_array($key, ['calendar-categories', 'calendar-event-types', 'calendar-events', 'events'], true) => 'calendar',
            in_array($key, ['booking-event-types', 'booking-links', 'bookings'], true) => 'booking',
            in_array($key, ['announcements', 'email-templates', 'email-template-tokens', 'daily-quotes'], true) => 'communication',
            in_array($key, ['notification-types', 'notification-triggers', 'notification-templates', 'notifications', 'notification-escalation-rules'], true) => 'notifications',
            in_array($key, ['teams', 'profile-completion-fields', 'task-categories', 'tasks', 'task-users'], true) => 'organization',
            $key === 'resources' => 'resources',
            default => 'other',
        };
    }

    /**
     * @return array<string, string>
     */
    private function resourceCategories(): array
    {
        return [
            'rank' => 'Rank & Recognition',
            'checklist' => 'Checklists',
            'training' => 'Training',
            'calendar' => 'Calendar & Events',
            'booking' => 'Booking',
            'communication' => 'Communication',
            'notifications' => 'Notifications & Alerts',
            'organization' => 'Organization',
            'resources' => 'Document Library',
            'other' => 'Other',
        ];
    }

    private function fieldMap(array $config): array
    {
        return collect($config['fields'])->keyBy('name')->all();
    }

    private function canViewManagementIndex(): bool
    {
        return auth()->user()->hasAnyRole(['super-admin', 'admin']);
    }

    private function canManageResource(string $resource): bool
    {
        if ($resource === 'resources') {
            return auth()->user()->canManageDocuments();
        }

        if (in_array($resource, ['assessments', 'questions', 'answers'], true)) {
            return auth()->user()->can('manage assessments')
                || auth()->user()->hasAnyRole(['super-admin', 'admin', 'trainer']);
        }

        if (in_array($resource, ['training-categories', 'training-modules', 'training-lessons'], true)) {
            return auth()->user()->can('manage training')
                || auth()->user()->hasAnyRole(['super-admin', 'admin', 'trainer']);
        }

        if (auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $this->isChecklistResource($resource)
            && auth()->user()->hasRole('agency-owner');
    }

    private function canViewResource(string $resource): bool
    {
        if ($resource === 'resources') {
            return auth()->user()->canManageDocuments();
        }

        if (in_array($resource, ['assessments', 'questions', 'answers'], true)) {
            return auth()->user()->can('manage assessments')
                || auth()->user()->hasAnyRole(['super-admin', 'admin', 'trainer']);
        }

        if (in_array($resource, ['training-categories', 'training-modules', 'training-lessons'], true)) {
            return auth()->user()->can('manage training')
                || auth()->user()->hasAnyRole(['super-admin', 'admin', 'trainer']);
        }

        if (auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $this->isChecklistResource($resource)
            && auth()->user()->hasAnyRole(['agency-owner', 'team-leader', 'certified-field-mentor', 'trainer']);
    }

    private function canDeleteResourceRecords(string $resource): bool
    {
        if ($resource === 'resources') {
            return auth()->user()->canDeleteDocuments();
        }

        return $this->canManageResource($resource);
    }

    private function canUpdateResourceRecord(string $resource, object $record): bool
    {
        if ($resource === 'resources') {
            return auth()->user()->canUpdateDocument($record);
        }

        return $this->canManageResource($resource);
    }

    private function denyPortalResourceUpdate(string $resource, object $record): ?RedirectResponse
    {
        if ($resource !== 'resources' || $this->canUpdateResourceRecord($resource, $record)) {
            return null;
        }

        return redirect()
            ->back()
            ->with('error', 'You can only update documents that you created. Contact an administrator if this record needs changes.');
    }

    private function isChecklistResource(string $resource): bool
    {
        return in_array($resource, [
            'checklists',
            'checklist-types',
            'checklist-instructions',
        ], true);
    }

    private function isSeederUpdatableResource(string $resource): bool
    {
        return in_array($resource, ['email-templates', 'email-template-tokens', 'checklists', 'checklist-types', 'task-categories', 'tasks', 'daily-quotes'], true);
    }

    private function canUpdateSeeder(string $resource): bool
    {
        if (! $this->isSeederUpdatableResource($resource)) {
            return false;
        }

        if (in_array($resource, ['checklists', 'checklist-types', 'task-categories', 'tasks', 'daily-quotes'], true)) {
            return auth()->user()->hasAnyRole(['super-admin', 'admin']);
        }

        return $this->canManageResource($resource);
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $columns = [];

        $columns[$table] ??= DB::getSchemaBuilder()->getColumnListing($table);

        return in_array($column, $columns[$table], true);
    }

    private function seederPath(string $resource): string
    {
        return match ($resource) {
            'email-templates' => database_path('seeders/EmailTemplateSeeder.php'),
            'email-template-tokens' => database_path('seeders/EmailTemplateTokenSeeder.php'),
            'checklists' => database_path('seeders/ChecklistSeeder.php'),
            'checklist-types' => database_path('seeders/ChecklistTypeSeeder.php'),
            'task-categories' => database_path('seeders/TaskCategorySeeder.php'),
            'tasks' => database_path('seeders/TaskSeeder.php'),
            'daily-quotes' => database_path('seeders/DailyQuoteSeeder.php'),
        };
    }

    private function writeChecklistSeeders(Request $request): void
    {
        $records = $this->checklistRecordsForSeederExport();

        File::put(
            database_path('seeders/data/cfm_mentoring_phases.php'),
            $this->buildCfmMentoringPhasesFile($records->get('cfm-mentoring', collect())),
        );

        File::put($this->seederPath('checklists'), $this->buildChecklistSeeder($records));
    }

    private function checklistRecordsForSeederExport(): Collection
    {
        return DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->whereNull('checklists.deleted_at')
            ->orderBy('checklist_types.sort_order')
            ->orderBy('checklists.sort_order')
            ->orderBy('checklists.id')
            ->get([
                'checklists.title',
                'checklists.description',
                'checklists.sort_order',
                'checklists.nth_day',
                'checklists.is_required',
                'checklists.requires_confirmation',
                'checklists.responsible_parties',
                'checklists.notified_parties',
                'checklists.country',
                'checklists.group_label',
                'checklists.phase_number',
                'checklists.phase_title',
                'checklists.phase_target',
                'checklists.section_title',
                'checklists.slug',
                'checklists.action_url',
                'checklists.action_label',
                'checklists.is_active',
                'checklist_types.code as type_code',
            ])
            ->groupBy('type_code');
    }

    private function buildCfmMentoringPhasesFile(Collection $items): string
    {
        $phases = $items
            ->sortBy('sort_order')
            ->groupBy('phase_number')
            ->map(function (Collection $phaseItems, $phaseNumber): array {
                $first = $phaseItems->first();

                return [
                    'phase_number' => (int) $phaseNumber,
                    'phase_title' => $first->phase_title,
                    'phase_target' => $first->phase_target,
                    'sections' => $phaseItems
                        ->groupBy('section_title')
                        ->map(fn (Collection $sectionItems) => $sectionItems
                            ->sortBy('sort_order')
                            ->pluck('title')
                            ->values()
                            ->all())
                        ->all(),
                ];
            })
            ->sortBy('phase_number')
            ->values()
            ->all();

        return "<?php\n\nreturn ".$this->exportPhpArray($phases).";\n";
    }

    private function buildChecklistSeeder(Collection $recordsByType): string
    {
        $methods = [];

        foreach ([
            'onboarding' => 'seedOnboarding',
            'licensing' => 'seedLicensing',
            'contracting' => 'seedContracting',
            'fap' => 'seedFap',
            'cfm-training' => 'seedCfmTraining',
        ] as $typeCode => $methodName) {
            $methods[] = $this->buildChecklistTypeSeedMethod(
                $typeCode,
                $methodName,
                $recordsByType->get($typeCode, collect()),
            );
        }

        $methods[] = <<<'PHP'
    private function seedCfmMentoring(): void
    {
        $phases = require __DIR__.'/data/cfm_mentoring_phases.php';
        $sortOrder = 0;

        foreach ($phases as $phase) {
            foreach ($phase['sections'] as $sectionTitle => $items) {
                foreach ($items as $title) {
                    $sortOrder++;
                    $slug = Str::slug('phase_'.$phase['phase_number'].'_'.Str::slug($title));

                    Checklist::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'checklist_type_id' => $this->typeId('cfm-mentoring'),
                            'title' => $title,
                            'phase_number' => $phase['phase_number'],
                            'phase_title' => $phase['phase_title'],
                            'phase_target' => $phase['phase_target'],
                            'section_title' => $sectionTitle,
                            'sort_order' => $sortOrder,
                            'is_required' => true,
                            'requires_confirmation' => true,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
PHP;

        $methodBody = implode("\n\n", $methods);

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\Checklist;
use App\\Models\\ChecklistType;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Str;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        \$this->seedOnboarding();
        \$this->seedLicensing();
        \$this->seedContracting();
        \$this->seedFap();
        \$this->seedCfmTraining();
        \$this->seedCfmMentoring();
    }

    private function typeId(string \$code): int
    {
        return (int) ChecklistType::query()->where('code', \$code)->value('id');
    }

    private function upsertItem(string \$typeCode, string \$title, array \$attributes): void
    {
        Checklist::query()->updateOrCreate(
            [
                'checklist_type_id' => \$this->typeId(\$typeCode),
                'title' => \$title,
            ],
            array_merge(['is_active' => true], \$attributes),
        );
    }

{$methodBody}
}

PHP;
    }

    private function buildChecklistTypeSeedMethod(string $typeCode, string $methodName, Collection $items): string
    {
        if ($items->isEmpty()) {
            return <<<PHP
    private function {$methodName}(): void
    {
    }
PHP;
        }

        $steps = [];
        $responsibleParties = [];
        $notifiedParties = [];

        foreach ($items->sortBy('sort_order')->values() as $item) {
            $step = [
                'title' => $item->title,
                'description' => $item->description,
                'sort_order' => (int) $item->sort_order,
                'nth_day' => $item->nth_day !== null ? (int) $item->nth_day : null,
                'is_required' => (bool) $item->is_required,
                'requires_confirmation' => (bool) $item->requires_confirmation,
            ];

            if ($typeCode === 'onboarding') {
                $step['country'] = $item->country;
            }

            $steps[] = $step;

            if (filled($item->responsible_parties)) {
                $responsibleParties[$item->title] = $item->responsible_parties;
            }

            if (filled($item->notified_parties)) {
                $notifiedParties[$item->title] = $item->notified_parties;
            }
        }

        $exportedSteps = $this->exportPhpArray($steps, 2);
        $exportedResponsibleParties = $this->exportPhpArray($responsibleParties, 2);
        $exportedNotifiedParties = $this->exportPhpArray($notifiedParties, 2);

        $groupLabelBlock = '';

        if ($typeCode === 'fap') {
            $groupLabel = $items->first()->group_label ?: 'Field Apprenticeship Program';
            $exportedGroupLabel = var_export($groupLabel, true);
            $groupLabelBlock = "\n        \$groupLabel = {$exportedGroupLabel};\n";
        }

        $upsertAttributes = match ($typeCode) {
            'onboarding' => <<<'ATTR'
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'],
                'country' => $step['country'],
ATTR,
            'fap' => <<<'ATTR'
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'group_label' => $groupLabel,
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'],
ATTR,
            default => <<<'ATTR'
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'],
ATTR,
        };

        return <<<PHP
    private function {$methodName}(): void
    {{$groupLabelBlock}
        \$steps = {$exportedSteps};

        \$responsibleParties = {$exportedResponsibleParties};

        \$notifiedParties = {$exportedNotifiedParties};

        foreach (\$steps as \$step) {
            \$this->upsertItem('{$typeCode}', \$step['title'], [
{$upsertAttributes}
            ]);
        }
    }
PHP;
    }

    private function buildEmailTemplateSeeder(): string
    {
        $templates = DB::table('email_templates')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['key', 'name', 'subject', 'body', 'token_values', 'is_active'])
            ->map(function ($template) {
                $tokenValues = $template->token_values;

                if (is_string($tokenValues)) {
                    $tokenValues = json_decode($tokenValues, true);
                }

                return [
                    'key' => $template->key,
                    'name' => $template->name,
                    'subject' => $template->subject,
                    'body' => $template->body,
                    'token_values' => is_array($tokenValues) && $tokenValues !== [] ? $tokenValues : null,
                    'is_active' => (bool) $template->is_active,
                ];
            })
            ->all();

        $exportedTemplates = $this->exportPhpArray($templates, 2);

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\EmailTemplate;
use Illuminate\\Database\\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        \$templates = {$exportedTemplates};

        foreach (\$templates as \$template) {
            EmailTemplate::updateOrCreate(
                ['key' => \$template['key']],
                [
                    'name' => \$template['name'],
                    'subject' => \$template['subject'],
                    'body' => \$template['body'],
                    'token_values' => \$template['token_values'] ?? null,
                    'is_active' => \$template['is_active'],
                ]
            );
        }
    }
}

PHP;
    }

    private function buildEmailTemplateTokenSeeder(): string
    {
        $tokens = DB::table('email_template_tokens')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['key', 'name', 'description', 'sample_value', 'sort_order', 'is_active'])
            ->map(fn ($token) => [
                'key' => $token->key,
                'name' => $token->name,
                'description' => $token->description,
                'sample_value' => $token->sample_value,
                'sort_order' => (int) $token->sort_order,
                'is_active' => (bool) $token->is_active,
            ])
            ->all();

        $exportedTokens = $this->exportPhpArray($tokens, 2);

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\EmailTemplateToken;
use Illuminate\\Database\\Seeder;

class EmailTemplateTokenSeeder extends Seeder
{
    public function run(): void
    {
        \$tokens = {$exportedTokens};

        foreach (\$tokens as \$token) {
            EmailTemplateToken::updateOrCreate(
                ['key' => \$token['key']],
                [
                    'name' => \$token['name'],
                    'description' => \$token['description'],
                    'sample_value' => \$token['sample_value'],
                    'sort_order' => \$token['sort_order'],
                    'is_active' => \$token['is_active'],
                ]
            );
        }
    }
}

PHP;
    }

    private function buildChecklistTypeSeeder(): string
    {
        $types = DB::table('checklist_types')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'description', 'icon', 'sort_order', 'max_complete_days', 'is_active']);

        $prerequisiteCodesByType = DB::table('checklist_type_prerequisites')
            ->join('checklist_types as checklist_types', 'checklist_types.id', '=', 'checklist_type_prerequisites.checklist_type_id')
            ->join('checklist_types as prerequisites', 'prerequisites.id', '=', 'checklist_type_prerequisites.prerequisite_checklist_type_id')
            ->whereNull('checklist_types.deleted_at')
            ->orderBy('prerequisites.sort_order')
            ->orderBy('prerequisites.name')
            ->get([
                'checklist_types.code as type_code',
                'prerequisites.code as prerequisite_code',
            ])
            ->groupBy('type_code')
            ->map(fn (Collection $rows) => $rows->pluck('prerequisite_code')->values()->all());

        $exportedTypes = $this->exportPhpArray(
            $types->map(fn ($type) => [
                'code' => $type->code,
                'name' => $type->name,
                'description' => $type->description,
                'icon' => $type->icon,
                'sort_order' => (int) $type->sort_order,
                'max_complete_days' => $type->max_complete_days !== null ? (int) $type->max_complete_days : null,
                'prerequisite_codes' => $prerequisiteCodesByType->get($type->code, []),
                'is_active' => (bool) $type->is_active,
            ])->all(),
            2,
        );

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\ChecklistType;
use Illuminate\\Database\\Seeder;

class ChecklistTypeSeeder extends Seeder
{
    public function run(): void
    {
        \$types = {$exportedTypes};

        foreach (\$types as \$type) {
            \$checklistType = ChecklistType::query()->updateOrCreate(
                ['code' => \$type['code']],
                [
                    'name' => \$type['name'],
                    'description' => \$type['description'],
                    'icon' => \$type['icon'],
                    'sort_order' => \$type['sort_order'],
                    'max_complete_days' => \$type['max_complete_days'],
                    'is_active' => \$type['is_active'],
                ],
            );

            \$prerequisiteIds = collect(\$type['prerequisite_codes'] ?? [])
                ->map(fn (string \$code) => ChecklistType::query()->where('code', \$code)->value('id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            \$checklistType->prerequisites()->sync(\$prerequisiteIds);
        }
    }
}

PHP;
    }

    private function buildTaskCategorySeeder(): string
    {
        $categories = DB::table('task_categories')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'name',
                'slug',
                'description',
                'action_route',
                'action_url',
                'action_label',
                'icon',
                'accent_class',
                'sort_order',
                'is_active',
            ]);

        $exportedCategories = $this->exportPhpArray(
            $categories->map(fn (object $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'action_route' => $category->action_route,
                'action_url' => $category->action_url,
                'action_label' => $category->action_label,
                'icon' => $category->icon,
                'accent_class' => $category->accent_class,
                'sort_order' => (int) $category->sort_order,
                'is_active' => (bool) $category->is_active,
            ])->all(),
            2,
        );

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\TaskCategory;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Str;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        \$categories = {$exportedCategories};

        foreach (\$categories as \$category) {
            TaskCategory::query()->updateOrCreate(
                ['slug' => \$category['slug'] ?? Str::slug(\$category['name'])],
                [
                    ...\$category,
                    'slug' => \$category['slug'] ?? Str::slug(\$category['name']),
                ],
            );
        }
    }
}

PHP;
    }

    private function buildDailyQuoteSeeder(): string
    {
        $quotes = DB::table('daily_quotes')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['quote', 'author', 'is_active', 'sort_order']);

        $exportedQuotes = $this->exportPhpArray(
            $quotes->map(fn (object $quote): array => [
                'quote' => $quote->quote,
                'author' => $quote->author,
                'is_active' => (bool) $quote->is_active,
                'sort_order' => (int) $quote->sort_order,
            ])->all(),
            2,
        );

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\DailyQuote;
use Illuminate\\Database\\Seeder;

class DailyQuoteSeeder extends Seeder
{
    public function run(): void
    {
        \$quotes = {$exportedQuotes};

        foreach (\$quotes as \$index => \$entry) {
            DailyQuote::query()->withTrashed()->updateOrCreate(
                ['quote' => \$entry['quote']],
                [
                    'author' => \$entry['author'] ?? null,
                    'is_active' => (bool) (\$entry['is_active'] ?? true),
                    'sort_order' => (int) (\$entry['sort_order'] ?? (\$index + 1)),
                    'deleted_at' => null,
                ],
            );
        }
    }
}

PHP;
    }

    private function buildTaskSeeder(): string
    {
        $tasks = DB::table('tasks')
            ->join('task_categories', 'tasks.task_category_id', '=', 'task_categories.id')
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.sort_order')
            ->orderBy('tasks.id')
            ->get([
                'task_categories.name as category_name',
                'tasks.title',
                'tasks.description',
                'tasks.slug',
                'tasks.default_priority',
                'tasks.related_module',
                'tasks.sort_order',
                'tasks.is_active',
            ]);

        $exportedTasks = $this->exportPhpArray(
            $tasks->map(fn (object $task): array => [
                'category' => $task->category_name,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->default_priority,
                'module' => $task->related_module,
                'slug' => $task->slug,
                'sort_order' => (int) $task->sort_order,
                'is_active' => (bool) $task->is_active,
            ])->all(),
            2,
        );

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\Task;
use App\\Models\\TaskCategory;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Str;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        \$definitions = {$exportedTasks};

        foreach (\$definitions as \$index => \$definition) {
            \$category = TaskCategory::query()->where('name', \$definition['category'])->first();

            if (! \$category) {
                continue;
            }

            \$slug = \$definition['slug'] ?? Str::slug(\$definition['title']);

            Task::query()->updateOrCreate(
                ['slug' => \$slug],
                [
                    'task_category_id' => \$category->id,
                    'title' => \$definition['title'],
                    'description' => \$definition['description'] ?? null,
                    'default_priority' => \$definition['priority'] ?? 'medium',
                    'related_module' => \$definition['module'] ?? null,
                    'sort_order' => \$definition['sort_order'] ?? ((\$index + 1) * 10),
                    'is_active' => \$definition['is_active'] ?? true,
                ],
            );
        }
    }
}

PHP;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeTaskData(array $validated): array
    {
        if (array_key_exists('slug', $validated) && blank($validated['slug']) && filled($validated['title'] ?? null)) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeTaskUserData(array $validated): array
    {
        if (! empty($validated['task_id'])) {
            $categoryId = DB::table('tasks')->where('id', $validated['task_id'])->value('task_category_id');

            if ($categoryId) {
                $validated['task_category_id'] = $categoryId;
            }
        }

        foreach (['due_date', 'completed_at', 'reminder', 'related_person', 'related_module', 'additional_notes'] as $nullableField) {
            if (array_key_exists($nullableField, $validated) && blank($validated[$nullableField])) {
                $validated[$nullableField] = null;
            }
        }

        if (array_key_exists('progress', $validated)) {
            $validated['progress'] = filled($validated['progress']) ? (int) $validated['progress'] : 0;
        }

        if (($validated['status'] ?? '') === 'completed' && empty($validated['completed_at'])) {
            $validated['completed_at'] = now();
        }

        return $validated;
    }

    private function resolveResourceOrderBy(Request $request, array $config): string
    {
        if (! ($config['sortable'] ?? false)) {
            return $config['order_by'] ?? 'id';
        }

        $sort = $request->string('sort')->toString();
        $allowed = $config['sort_columns'] ?? ['sort_order', 'name'];

        return in_array($sort, $allowed, true) ? $sort : ($config['order_by'] ?? 'sort_order');
    }

    private function resolveResourceOrderDirection(Request $request, array $config): string
    {
        if (! ($config['sortable'] ?? false)) {
            return $config['order_direction'] ?? 'asc';
        }

        $direction = $request->string('direction')->toString();

        return in_array($direction, ['asc', 'desc'], true) ? $direction : ($config['order_direction'] ?? 'asc');
    }

    private function exportPhpArray(array $value, int $indent = 0): string
    {
        $export = var_export($value, true);
        $export = preg_replace('/^([ ]*)array \\(/m', '$1[', $export);
        $export = preg_replace('/\\)(,?)$/m', ']$1', $export);

        if ($indent === 0) {
            return $export;
        }

        $spaces = str_repeat(' ', $indent * 4);

        return preg_replace('/^/m', $spaces, $export);
    }

    private function formOptionsFor(array $config): array
    {
        $types = collect($config['fields'])->pluck('type')->unique()->all();
        $needs = fn (string ...$fieldTypes): bool => array_intersect($types, $fieldTypes) !== [];

        $options = [];

        if ($needs('user')) {
            $options['users'] = DB::table('users')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'email']);
        }

        if ($needs('team')) {
            $options['teams'] = DB::table('teams')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        }

        if ($needs('training_category')) {
            $options['training_categories'] = DB::table('training_categories')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        }

        if ($needs('training_module', 'training_module_optional')) {
            $options['training_modules'] = DB::table('training_modules')->whereNull('deleted_at')->orderBy('title')->get(['id', 'title']);
        }

        if ($needs('assessment')) {
            $options['assessments'] = DB::table('assessments')->whereNull('deleted_at')->orderBy('title')->get(['id', 'title']);
        }

        if ($needs('question')) {
            $options['questions'] = DB::table('questions')->whereNull('deleted_at')->orderBy('question')->get(['id', 'question']);
        }

        if ($needs('rank')) {
            $options['ranks'] = DB::table('ranks')->whereNull('deleted_at')->orderBy('sort_order')->get(['id', 'code', 'name']);
        }

        if ($needs('checklist_type', 'checklist_types')) {
            $options['checklist_types'] = ChecklistType::query()
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name']);
        }

        if ($needs('calendar_category')) {
            $options['calendar_categories'] = DB::table('calendar_categories')->whereNull('deleted_at')->orderBy('sort_order')->get(['id', 'name']);
        }

        if ($needs('calendar_event_type')) {
            $options['calendar_event_types'] = DB::table('calendar_event_types')->whereNull('deleted_at')->orderBy('sort_order')->get(['id', 'name']);
        }

        if ($needs('booking_event_type')) {
            $options['booking_event_types'] = DB::table('booking_event_types')->whereNull('deleted_at')->orderBy('title')->get(['id', 'title']);
        }

        if ($needs('availability_schedule')) {
            $options['availability_schedules'] = DB::table('availability_schedules')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        }

        if ($needs('notification_type')) {
            $options['notification_types'] = DB::table('notification_types')->whereNull('deleted_at')->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name']);
        }

        if ($needs('notification_trigger')) {
            $options['notification_triggers'] = DB::table('notification_triggers')->whereNull('deleted_at')->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name']);
        }

        if ($needs('notification_template')) {
            $options['notification_templates'] = DB::table('notification_templates')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        }

        if ($needs('task_category')) {
            $options['task_categories'] = DB::table('task_categories')->whereNull('deleted_at')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        }

        if ($needs('task')) {
            $options['tasks'] = DB::table('tasks')->whereNull('deleted_at')->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'task_category_id']);
        }

        return $options;
    }

    private function resources(): array
    {
        return [
            'ranks' => [
                'table' => 'ranks',
                'label' => 'Ranks',
                'description' => 'Manage Greatway promotion levels, FYO compensation rates, and active status.',
                'order_by' => 'sort_order',
                'search' => ['code', 'name'],
                'columns' => ['code', 'name', 'track', 'total_fyo_rate', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'track', 'label' => 'Track', 'type' => 'select', 'options' => ['producer' => 'Producer', 'builder' => 'Builder'], 'rules' => ['required', 'string', 'in:producer,builder']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'between:0,65535'], 'unique' => true],
                    ['name' => 'income_metric', 'label' => 'Income Metric', 'type' => 'select', 'options' => ['' => 'None', 'lti' => 'Lifetime Income (LTI)', 'twelve_month_income' => '12 Month Income (12MI)'], 'rules' => ['nullable', 'string', 'in:lti,twelve_month_income']],
                    ['name' => 'income_requirement', 'label' => 'Income Requirement', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['name' => 'qualification', 'label' => 'Qualification', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'nba_requirement', 'label' => 'NBA Requirement', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['name' => 'mb_leg_requirement', 'label' => 'MB Leg Requirement', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['name' => 'svp_leg_requirement', 'label' => 'SVP Leg Requirement', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['name' => 'svp_leg_alternate', 'label' => 'SVP Alternate Legs', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['name' => 'compensation_rate', 'label' => 'Compensation Rate %', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0']],
                    ['name' => 'ppb_rate', 'label' => 'PPB Rate %', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0']],
                    ['name' => 'total_fyo_rate', 'label' => 'Total FYO Rate %', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0']],
                    ['name' => 'non_fyc_rate', 'label' => 'Other Provider Non-FYC %', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0']],
                    ['name' => 'is_field_leader', 'label' => 'Field Leader (SVP+)', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'daily-quotes' => [
                'table' => 'daily_quotes',
                'label' => 'Daily Quotes',
                'description' => 'Manage inspirational quotes shown on My Dashboard. Active quotes rotate automatically each day.',
                'order_by' => 'sort_order',
                'order_direction' => 'asc',
                'sortable' => true,
                'sort_columns' => ['sort_order', 'author', 'created_at'],
                'search' => ['quote', 'author'],
                'columns' => ['quote', 'author', 'sort_order'],
                'fields' => [
                    ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:2000']],
                    ['name' => 'author', 'label' => 'Author', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'help' => 'Lower numbers appear earlier in the daily rotation sequence.', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'help' => 'Only active quotes appear on My Dashboard.', 'rules' => ['required', 'boolean']],
                ],
            ],
            'profile-completion-fields' => $this->profileCompletionFieldsResource(),
            'teams' => [
                'table' => 'teams',
                'label' => 'Teams',
                'description' => 'Manage team hierarchy, owners, leaders, and status.',
                'order_by' => 'name',
                'search' => ['name', 'description'],
                'columns' => ['name', 'owner_id', 'leader_id', 'is_active'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'owner_id', 'label' => 'Owner', 'type' => 'user', 'rules' => ['nullable', 'integer', 'exists:users,id']],
                    ['name' => 'leader_id', 'label' => 'Leader', 'type' => 'user', 'rules' => ['nullable', 'integer', 'exists:users,id']],
                    ['name' => 'parent_id', 'label' => 'Parent Team', 'type' => 'team', 'rules' => ['nullable', 'integer', 'exists:teams,id']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'checklists' => [
                'table' => 'checklists',
                'label' => 'Checklists',
                'description' => 'Manage checklist items for onboarding, licensing, contracting, FAP, CFM training, mentoring, and other checklist types.',
                'use_inline_modals' => true,
                'order_by' => 'sort_order',
                'search' => ['title', 'description', 'slug', 'group_label', 'country', 'section_title', 'phase_title'],
                'columns' => ['checklist_type_id', 'title', 'sort_order', 'nth_day', 'is_required', 'requires_confirmation'],
                'fields' => [
                    ['name' => 'checklist_type_id', 'label' => 'Checklist Type', 'type' => 'checklist_type', 'rules' => ['required', 'integer', 'exists:checklist_types,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'nth_day', 'label' => 'Nth Day', 'type' => 'number', 'help' => 'Expected completion day from the member start date for this checklist type (Day 1 = start date).', 'rules' => ['nullable', 'integer', 'min:1', 'max:3650']],
                    ['name' => 'is_required', 'label' => 'Required', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'requires_confirmation', 'label' => 'Confirmation Needed', 'type' => 'boolean', 'help' => 'If Yes, a confirmer (such as the CFM) must approve after the member marks this complete. If No, the item is approved automatically.', 'rules' => ['required', 'boolean']],
                    ['name' => 'responsible_parties', 'label' => 'Responsible Parties', 'type' => 'responsible_parties', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'notified_parties', 'label' => 'Notified Parties', 'type' => 'notified_parties', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'country', 'label' => 'Country Applicability', 'type' => 'select', 'options' => ['' => 'Global - all countries', 'Canada' => 'Canada', 'United States' => 'United States', 'Philippines' => 'Philippines', 'Mexico' => 'Mexico'], 'rules' => ['nullable', 'string', 'in:Canada,United States,Philippines,Mexico']],
                    ['name' => 'group_label', 'label' => 'Group Label', 'type' => 'text', 'help' => 'Optional program or grouping label (e.g. Field Apprenticeship Program).', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'phase_number', 'label' => 'Phase Number', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1']],
                    ['name' => 'phase_title', 'label' => 'Phase Title', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'phase_target', 'label' => 'Phase Target', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'section_title', 'label' => 'Section Title', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'help' => 'Unique per checklist type. Used for CFM mentoring action links.', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'action_url', 'label' => 'Action URL', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:500']],
                    ['name' => 'action_label', 'label' => 'Action Label', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'checklist-types' => [
                'table' => 'checklist_types',
                'label' => 'Checklist Types',
                'description' => 'Manage checklist categories such as onboarding, licensing, contracting, FAP, CFM training, and mentoring.',
                'use_inline_modals' => false,
                'order_by' => 'sort_order',
                'search' => ['code', 'name', 'description'],
                'columns' => ['code', 'name', 'sort_order', 'max_complete_days', 'prerequisites_label'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'help' => 'Stable identifier used in code (lowercase, hyphens).', 'rules' => ['required', 'string', 'max:50'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'help' => 'Optional icon key for UI display.', 'rules' => ['nullable', 'string', 'max:100']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'max_complete_days', 'label' => 'Max Complete Days', 'type' => 'number', 'help' => 'Maximum days from the member start date to complete this checklist type (Day 1 = start date).', 'rules' => ['nullable', 'integer', 'min:1', 'max:3650']],
                    ['name' => 'prerequisite_checklist_type_ids', 'label' => 'Prerequisites', 'type' => 'checklist_types', 'virtual' => true, 'help' => 'Checklist types that must be completed before this one can be started. Hold Ctrl or Cmd to select multiple.', 'rules' => []],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'checklist-instructions' => [
                'table' => 'checklist_instructions',
                'label' => 'Checklist Instructions',
                'description' => 'Additional rich-text guidance, manual links, and reference links to help members complete each checklist type.',
                'use_inline_modals' => false,
                'order_by' => 'sort_order',
                'search' => ['instructions', 'doc_link', 'other_link'],
                'columns' => ['checklist_type_id', 'doc_link', 'other_link', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'checklist_type_id', 'label' => 'Checklist Type', 'type' => 'checklist_type', 'rules' => ['required', 'integer', 'exists:checklist_types,id']],
                    ['name' => 'instructions', 'label' => 'Instructions', 'type' => 'rich_text', 'rows' => 16, 'help' => 'Rich text shown to members completing this checklist (formatting, lists, and links supported).', 'rules' => ['nullable', 'string']],
                    ['name' => 'doc_link', 'label' => 'Manual / Document Link', 'type' => 'text', 'help' => 'Link to a manual or document library entry. Full URL or site-relative path.', 'rules' => ['nullable', 'string', 'max:500', new UrlOrRelativePath]],
                    ['name' => 'other_link', 'label' => 'Other Link', 'type' => 'text', 'help' => 'Optional secondary resource such as a video or external reference.', 'rules' => ['nullable', 'string', 'max:500', new UrlOrRelativePath]],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'training-categories' => [
                'table' => 'training_categories',
                'label' => 'Training Categories',
                'description' => 'Manage training category names, slugs, and ordering.',
                'order_by' => 'sort_order',
                'search' => ['name', 'slug', 'description'],
                'columns' => ['name', 'slug', 'sort_order'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'training-modules' => [
                'table' => 'training_modules',
                'label' => 'Training Modules',
                'description' => 'Manage training modules under each training category.',
                'order_by' => 'sort_order',
                'search' => ['title', 'slug', 'description'],
                'columns' => ['title', 'slug', 'sort_order', 'is_published'],
                'fields' => [
                    ['name' => 'training_category_id', 'label' => 'Training Category', 'type' => 'training_category', 'rules' => ['required', 'integer', 'exists:training_categories,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_published', 'label' => 'Published', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'training-lessons' => [
                'table' => 'training_lessons',
                'label' => 'Training Lessons',
                'description' => 'Manage lesson content and video links inside training modules.',
                'order_by' => 'sort_order',
                'search' => ['title', 'content', 'video_url'],
                'columns' => ['title', 'video_url', 'sort_order'],
                'fields' => [
                    ['name' => 'training_module_id', 'label' => 'Training Module', 'type' => 'training_module', 'rules' => ['required', 'integer', 'exists:training_modules,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'video_url', 'label' => 'Video URL', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'assessments' => [
                'table' => 'assessments',
                'label' => 'Assessments',
                'description' => 'Manage assessment definitions, passing scores, and publication status.',
                'order_by' => 'title',
                'search' => ['title', 'description'],
                'columns' => ['title', 'passing_score', 'is_published'],
                'fields' => [
                    ['name' => 'training_module_id', 'label' => 'Training Module', 'type' => 'training_module_optional', 'rules' => ['nullable', 'integer', 'exists:training_modules,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'passing_score', 'label' => 'Passing Score', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100']],
                    ['name' => 'is_published', 'label' => 'Published', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'questions' => [
                'table' => 'questions',
                'label' => 'Questions',
                'description' => 'Manage assessment questions and question type.',
                'order_by' => 'sort_order',
                'search' => ['question', 'type'],
                'columns' => ['question', 'type', 'sort_order'],
                'fields' => [
                    ['name' => 'assessment_id', 'label' => 'Assessment', 'type' => 'assessment', 'rules' => ['required', 'integer', 'exists:assessments,id']],
                    ['name' => 'question', 'label' => 'Question', 'type' => 'textarea', 'rules' => ['required', 'string']],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['multiple_choice' => 'Multiple Choice', 'true_false' => 'True / False', 'short_answer' => 'Short Answer'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'answers' => [
                'table' => 'answers',
                'label' => 'Answers',
                'description' => 'Manage possible answers and correct answer flags.',
                'order_by' => 'id',
                'search' => ['answer'],
                'columns' => ['answer', 'is_correct'],
                'fields' => [
                    ['name' => 'question_id', 'label' => 'Question', 'type' => 'question', 'rules' => ['required', 'integer', 'exists:questions,id']],
                    ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'rules' => ['required', 'string']],
                    ['name' => 'is_correct', 'label' => 'Correct Answer', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'rank-requirements' => [
                'table' => 'rank_requirements',
                'label' => 'Rank Requirements',
                'description' => 'Manage advancement requirements tied to each rank.',
                'order_by' => 'sort_order',
                'search' => ['title', 'description'],
                'columns' => ['rank_id', 'title', 'category', 'sort_order', 'is_required', 'is_active'],
                'fields' => [
                    ['name' => 'rank_id', 'label' => 'Rank', 'type' => 'rank', 'rules' => ['required', 'integer', 'exists:ranks,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => config('rank-advancement.categories', []), 'rules' => ['required', 'string', 'max:40']],
                    ['name' => 'is_required', 'label' => 'Required', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'resources' => [
                'table' => 'resources',
                'label' => 'Resources',
                'description' => 'Create and edit document library entries by composing rich content or uploading a PDF file for members to download.',
                'uses_creator' => true,
                'use_inline_modals' => false,
                'order_by' => 'sort_order',
                'search' => ['title', 'description', 'type', 'url', 'category'],
                'columns' => ['title', 'type', 'category', 'is_published', 'sort_order'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Summary', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['document' => 'Document', 'file' => 'File', 'link' => 'Link', 'video' => 'Video'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ResourceDocumentCategories::optionsForSelect(), 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'content', 'label' => 'Document Content', 'type' => 'rich_text', 'rows' => 18, 'help' => 'Compose the document body here. Use Generate PDF to convert this content into a downloadable PDF for the document library.', 'rules' => ['nullable', 'string']],
                    ['name' => 'url', 'label' => 'External URL (optional)', 'type' => 'text', 'help' => 'Optional fallback link. Enter a full URL or a site-relative path such as resources/documents/welcome-packet.pdf.', 'rules' => ['nullable', 'string', 'max:255', new UrlOrRelativePath]],
                    ['name' => 'is_published', 'label' => 'Published', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_featured', 'label' => 'Featured', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'events' => [
                'table' => 'events',
                'label' => 'Events',
                'description' => 'Manage calendar events, locations, and times.',
                'uses_creator' => true,
                'order_by' => 'starts_at',
                'search' => ['title', 'description', 'location'],
                'columns' => ['title', 'location', 'starts_at', 'ends_at'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'starts_at', 'label' => 'Starts At', 'type' => 'datetime-local', 'rules' => ['required', 'date']],
                    ['name' => 'ends_at', 'label' => 'Ends At', 'type' => 'datetime-local', 'rules' => ['nullable', 'date', 'after_or_equal:starts_at']],
                ],
            ],
            'calendar-categories' => [
                'table' => 'calendar_categories',
                'label' => 'Calendar Categories',
                'description' => 'Manage visible calendar groups such as Team, Training, Licensing, Prospects, FAP, CFM, and Organization.',
                'order_by' => 'sort_order',
                'search' => ['name', 'slug', 'icon'],
                'columns' => ['name', 'slug', 'color', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'rules' => ['required', 'string', 'max:20']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'calendar-event-types' => [
                'table' => 'calendar_event_types',
                'label' => 'Calendar Event Types',
                'description' => 'Manage reusable event types and colors for calendar workflows.',
                'order_by' => 'sort_order',
                'search' => ['name', 'slug', 'icon'],
                'columns' => ['name', 'slug', 'calendar_category_id', 'color', 'is_active'],
                'fields' => [
                    ['name' => 'calendar_category_id', 'label' => 'Calendar Category', 'type' => 'calendar_category', 'rules' => ['nullable', 'integer', 'exists:calendar_categories,id']],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'rules' => ['required', 'string', 'max:20']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'calendar-events' => [
                'table' => 'calendar_events',
                'label' => 'Calendar Events',
                'description' => 'Manage module calendar events and their schedule, visibility, organizer, and status.',
                'order_by' => 'starts_at',
                'search' => ['title', 'description', 'location', 'meeting_link'],
                'columns' => ['title', 'organizer_id', 'starts_at', 'status', 'visibility'],
                'fields' => [
                    ['name' => 'organizer_id', 'label' => 'Organizer', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'calendar_event_type_id', 'label' => 'Event Type', 'type' => 'calendar_event_type', 'rules' => ['nullable', 'integer', 'exists:calendar_event_types,id']],
                    ['name' => 'calendar_category_id', 'label' => 'Calendar Category', 'type' => 'calendar_category', 'rules' => ['nullable', 'integer', 'exists:calendar_categories,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'starts_at', 'label' => 'Starts At', 'type' => 'datetime-local', 'rules' => ['required', 'date']],
                    ['name' => 'ends_at', 'label' => 'Ends At', 'type' => 'datetime-local', 'rules' => ['nullable', 'date', 'after_or_equal:starts_at']],
                    ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'meeting_link', 'label' => 'Meeting Link', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['name' => 'visibility', 'label' => 'Visibility', 'type' => 'select', 'options' => ['private' => 'Private', 'shared_team' => 'Shared Team', 'shared_downline' => 'Shared Downline', 'public_organization' => 'Public Organization'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'draft' => 'Draft'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'rules' => ['required', 'string', 'max:20']],
                    ['name' => 'is_all_day', 'label' => 'All Day', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_recurring', 'label' => 'Recurring', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'recurrence_rule', 'label' => 'Recurrence Rule', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                ],
            ],
            'booking-event-types' => [
                'table' => 'booking_event_types',
                'label' => 'Booking Event Types',
                'description' => 'Manage CFM booking event types, duration, buffers, approval behavior, location, and link rules.',
                'order_by' => 'title',
                'search' => ['title', 'slug', 'description', 'event_category'],
                'columns' => ['title', 'owner_id', 'duration_minutes', 'approval_required', 'is_active'],
                'fields' => [
                    ['name' => 'owner_id', 'label' => 'Owner / CFM', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'calendar_category_id', 'label' => 'Calendar Category', 'type' => 'calendar_category', 'rules' => ['nullable', 'integer', 'exists:calendar_categories,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'duration_minutes', 'label' => 'Duration Minutes', 'type' => 'number', 'rules' => ['required', 'integer', 'min:5', 'max:480']],
                    ['name' => 'event_category', 'label' => 'Event Category', 'type' => 'select', 'options' => ['mentor_session' => 'Mentor Session', 'field_apprenticeship' => 'Field Apprenticeship', 'prospect_support' => 'Prospect Support', 'licensing' => 'Licensing', 'rank_coaching' => 'Rank Coaching'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'location_type', 'label' => 'Location Type', 'type' => 'select', 'options' => ['zoom' => 'Zoom', 'phone' => 'Phone', 'in_person' => 'In Person', 'custom' => 'Custom'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'meeting_link', 'label' => 'Meeting Link', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['name' => 'approval_required', 'label' => 'Approval Required', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'visibility', 'label' => 'Visibility', 'type' => 'select', 'options' => ['assigned_apprentices' => 'Assigned Apprentices', 'team' => 'Team', 'private_invite' => 'Private Invite', 'public' => 'Public'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'rules' => ['required', 'string', 'max:20']],
                    ['name' => 'buffer_before_minutes', 'label' => 'Buffer Before', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:240']],
                    ['name' => 'buffer_after_minutes', 'label' => 'Buffer After', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:240']],
                    ['name' => 'minimum_notice_minutes', 'label' => 'Minimum Notice Minutes', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'maximum_booking_days_ahead', 'label' => 'Maximum Booking Days Ahead', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:365']],
                    ['name' => 'daily_booking_limit', 'label' => 'Daily Booking Limit', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1', 'max:50']],
                    ['name' => 'weekly_booking_limit', 'label' => 'Weekly Booking Limit', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1', 'max:200']],
                    ['name' => 'confirmation_message', 'label' => 'Confirmation Message', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'cancellation_policy', 'label' => 'Cancellation Policy', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                ],
            ],
            'booking-links' => [
                'table' => 'booking_links',
                'label' => 'Booking Links',
                'description' => 'Manage personal, event-type, apprentice-specific, team, private, one-time, and expiring booking links.',
                'order_by' => 'name',
                'search' => ['name', 'slug', 'token', 'link_type'],
                'columns' => ['name', 'owner_id', 'link_type', 'visibility', 'is_active'],
                'fields' => [
                    ['name' => 'owner_id', 'label' => 'Owner / CFM', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'booking_event_type_id', 'label' => 'Booking Event Type', 'type' => 'booking_event_type', 'rules' => ['nullable', 'integer', 'exists:booking_event_types,id']],
                    ['name' => 'availability_schedule_id', 'label' => 'Availability Schedule', 'type' => 'availability_schedule', 'rules' => ['nullable', 'integer', 'exists:availability_schedules,id']],
                    ['name' => 'apprentice_id', 'label' => 'Apprentice', 'type' => 'user', 'rules' => ['nullable', 'integer', 'exists:users,id']],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'token', 'label' => 'Token', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'link_type', 'label' => 'Link Type', 'type' => 'select', 'options' => ['personal' => 'Personal CFM Page', 'event_type' => 'Event Type', 'apprentice' => 'Apprentice Specific', 'team' => 'Team', 'private_invite' => 'Private Invite', 'one_time' => 'One Time'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'visibility', 'label' => 'Visibility', 'type' => 'select', 'options' => ['public' => 'Public', 'private' => 'Private', 'invite_only' => 'Invite Only', 'team' => 'Team'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_one_time', 'label' => 'One Time', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'expires_at', 'label' => 'Expires At', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                    ['name' => 'max_uses', 'label' => 'Max Uses', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1']],
                ],
            ],
            'bookings' => [
                'table' => 'bookings',
                'label' => 'Bookings',
                'description' => 'Review mentor session bookings, requests, approvals, cancellations, and connected calendar events.',
                'order_by' => 'starts_at',
                'search' => ['status', 'reason', 'topics', 'meeting_link'],
                'columns' => ['booking_event_type_id', 'cfm_id', 'trainee_id', 'starts_at', 'status'],
                'fields' => [
                    ['name' => 'booking_event_type_id', 'label' => 'Booking Event Type', 'type' => 'booking_event_type', 'rules' => ['required', 'integer', 'exists:booking_event_types,id']],
                    ['name' => 'cfm_id', 'label' => 'CFM', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'trainee_id', 'label' => 'Trainee', 'type' => 'user', 'rules' => ['nullable', 'integer', 'exists:users,id']],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'pending_approval' => 'Pending Approval', 'confirmed' => 'Confirmed', 'declined' => 'Declined', 'cancelled' => 'Cancelled', 'rescheduled' => 'Rescheduled', 'completed' => 'Completed', 'no_show' => 'No Show', 'expired' => 'Expired'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'starts_at', 'label' => 'Starts At', 'type' => 'datetime-local', 'rules' => ['required', 'date']],
                    ['name' => 'ends_at', 'label' => 'Ends At', 'type' => 'datetime-local', 'rules' => ['required', 'date', 'after:starts_at']],
                    ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'location_type', 'label' => 'Location Type', 'type' => 'select', 'options' => ['zoom' => 'Zoom', 'phone' => 'Phone', 'in_person' => 'In Person', 'custom' => 'Custom'], 'rules' => ['required', 'string', 'max:100']],
                    ['name' => 'meeting_link', 'label' => 'Meeting Link', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'topics', 'label' => 'Topics', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'internal_notes', 'label' => 'Internal Notes', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                ],
            ],
            'announcements' => [
                'table' => 'announcements',
                'label' => 'Announcements',
                'description' => 'Manage team announcements and publish timing.',
                'uses_creator' => true,
                'order_by' => 'title',
                'search' => ['title', 'body'],
                'columns' => ['title', 'published_at'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'rules' => ['required', 'string']],
                    ['name' => 'published_at', 'label' => 'Published At', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                ],
            ],
            'badges' => [
                'table' => 'badges',
                'label' => 'Badges',
                'description' => 'Manage recognition badges and icon labels.',
                'order_by' => 'name',
                'search' => ['name', 'slug', 'description'],
                'columns' => ['name', 'slug', 'icon'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            'task-categories' => [
                'table' => 'task_categories',
                'label' => 'Task Categories',
                'description' => 'Manage task categories and the action links members use to complete work in each category.',
                'order_by' => 'sort_order',
                'order_direction' => 'asc',
                'sortable' => true,
                'sort_columns' => ['sort_order', 'name', 'slug', 'action_label'],
                'search' => ['name', 'slug', 'description', 'action_route', 'action_label'],
                'columns' => ['name', 'slug', 'action_route', 'action_label', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'help' => 'Displayed on tasks and in category filters.', 'rules' => ['required', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'help' => 'Stable identifier (auto-style: lowercase with hyphens).', 'rules' => ['required', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'action_route', 'label' => 'Action Route', 'type' => 'text', 'help' => 'Laravel route name, e.g. team.prospects or training.index.', 'rules' => ['nullable', 'string', 'max:120']],
                    ['name' => 'action_url', 'label' => 'Action URL', 'type' => 'text', 'help' => 'Optional explicit URL. Used when no route is set, or for external links.', 'rules' => ['nullable', 'string', 'max:500']],
                    ['name' => 'action_label', 'label' => 'Action Label', 'type' => 'text', 'help' => 'Button text shown on tasks in this category.', 'rules' => ['required', 'string', 'max:80']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:60']],
                    ['name' => 'accent_class', 'label' => 'Accent Classes', 'type' => 'text', 'help' => 'Tailwind classes for category badges.', 'rules' => ['nullable', 'string', 'max:120']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'tasks' => [
                'table' => 'tasks',
                'label' => 'Tasks',
                'description' => 'Manage reusable task templates in the task library used for assignments and workflows.',
                'order_by' => 'sort_order',
                'order_direction' => 'asc',
                'sortable' => true,
                'sort_columns' => ['sort_order', 'title', 'default_priority', 'task_category_id'],
                'search' => ['title', 'description', 'slug', 'related_module'],
                'columns' => ['task_category_id', 'title', 'default_priority', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'task_category_id', 'label' => 'Category', 'type' => 'task_category', 'rules' => ['required', 'integer', 'exists:task_categories,id']],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'help' => 'Stable identifier (auto-generated from title when left blank).', 'rules' => ['nullable', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'default_priority', 'label' => 'Default Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'], 'rules' => ['required', 'in:low,medium,high,urgent']],
                    ['name' => 'related_module', 'label' => 'Related Module', 'type' => 'text', 'help' => 'Optional module label such as Prospects, Training, or FNA.', 'rules' => ['nullable', 'string', 'max:60']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'task-users' => [
                'table' => 'task_users',
                'label' => 'Task Assignments',
                'description' => 'Review and manage task assignments across members, including status, due dates, and notes.',
                'use_inline_modals' => false,
                'order_by' => 'due_date',
                'order_direction' => 'desc',
                'sort_columns' => ['due_date', 'status', 'priority', 'created_at'],
                'search' => ['additional_notes', 'status', 'priority', 'related_person', 'related_module'],
                'columns' => ['assignee_id', 'assignor_id', 'task_id', 'status', 'priority', 'due_date'],
                'fields' => [
                    ['name' => 'assignee_id', 'label' => 'Assignee', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'assignor_id', 'label' => 'Assignor', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'task_id', 'label' => 'Task', 'type' => 'task', 'rules' => ['required', 'integer', 'exists:tasks,id']],
                    ['name' => 'task_category_id', 'label' => 'Category', 'type' => 'task_category', 'help' => 'Auto-filled from the selected task when saved.', 'rules' => ['required', 'integer', 'exists:task_categories,id']],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'], 'rules' => ['required', 'in:low,medium,high,urgent']],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['to_do' => 'To Do', 'in_progress' => 'In Progress', 'waiting' => 'Waiting', 'overdue' => 'Overdue', 'completed' => 'Completed', 'cancelled' => 'Cancelled'], 'rules' => ['required', 'in:to_do,in_progress,waiting,overdue,completed,cancelled']],
                    ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date', 'rules' => ['nullable', 'date']],
                    ['name' => 'progress', 'label' => 'Progress (%)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:100']],
                    ['name' => 'related_person', 'label' => 'Related Person', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                    ['name' => 'related_module', 'label' => 'Related Module', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:60']],
                    ['name' => 'additional_notes', 'label' => 'Additional Notes', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'reminder', 'label' => 'Reminder', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:40']],
                    ['name' => 'completed_at', 'label' => 'Completed At', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                ],
            ],
            'email-templates' => [
                'table' => 'email_templates',
                'label' => 'Email Templates',
                'description' => 'Manage transactional email subjects and body copy. Use merge tokens such as {{ member_name }} in subject and body; set per-template token values below the form fields. Inactive templates are not sent.',
                'use_inline_modals' => false,
                'order_by' => 'name',
                'search' => ['key', 'name', 'subject'],
                'columns' => ['key', 'name', 'subject', 'is_active'],
                'fields' => [
                    ['name' => 'key', 'label' => 'Key', 'type' => 'text', 'help' => 'Stable slug used in code (lowercase, underscores).', 'rules' => ['required', 'string', 'max:255'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'body', 'label' => 'Body', 'type' => 'rich_text', 'rows' => 14, 'help' => 'Use the editor for HTML formatting (paragraphs, lists, links). Merge tokens such as {{ member_name }} still work.', 'rules' => ['required', 'string']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'email-template-tokens' => [
                'table' => 'email_template_tokens',
                'label' => 'Email Template Tokens',
                'description' => 'Manage merge tokens available in email templates. Token keys are inserted as {{ token_key }} in template subject and body.',
                'use_inline_modals' => false,
                'order_by' => 'sort_order',
                'search' => ['key', 'name', 'description', 'sample_value'],
                'columns' => ['key', 'name', 'description', 'sample_value', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'key', 'label' => 'Token Key', 'type' => 'text', 'help' => 'Lowercase identifier used in templates, e.g. member_name for {{ member_name }}.', 'rules' => ['required', 'string', 'max:120', 'regex:/^[a-z][a-z0-9_]*$/'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Display Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3, 'help' => 'Explain when and how this token is populated when an email is sent.', 'rules' => ['nullable', 'string']],
                    ['name' => 'sample_value', 'label' => 'Sample Value', 'type' => 'text', 'help' => 'Optional example shown to admins when editing templates.', 'rules' => ['nullable', 'string', 'max:255']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'notification-types' => [
                'table' => 'notification_types',
                'label' => 'Notification Types',
                'description' => 'Manage notification categories shown in the inbox, preferences, and admin reporting.',
                'order_by' => 'sort_order',
                'search' => ['code', 'name', 'description'],
                'columns' => ['code', 'name', 'sort_order', 'is_active'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:50'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:20']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'notification-triggers' => [
                'table' => 'notification_triggers',
                'label' => 'Notification Triggers',
                'description' => 'Manage event triggers that fire notifications across modules.',
                'order_by' => 'sort_order',
                'search' => ['code', 'name', 'event_key', 'description'],
                'columns' => ['notification_type_id', 'code', 'name', 'event_key', 'is_active'],
                'fields' => [
                    ['name' => 'notification_type_id', 'label' => 'Notification Type', 'type' => 'notification_type', 'rules' => ['required', 'integer', 'exists:notification_types,id']],
                    ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:80'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                    ['name' => 'event_key', 'label' => 'Event Key', 'type' => 'text', 'rules' => ['required', 'string', 'max:120']],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'notification-templates' => [
                'table' => 'notification_templates',
                'label' => 'Notification Templates',
                'description' => 'Manage default copy and channels for each notification trigger.',
                'order_by' => 'name',
                'search' => ['name', 'subject', 'body'],
                'columns' => ['notification_trigger_id', 'name', 'subject', 'is_default', 'is_active'],
                'fields' => [
                    ['name' => 'notification_trigger_id', 'label' => 'Trigger', 'type' => 'notification_trigger', 'rules' => ['required', 'integer', 'exists:notification_triggers,id']],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'rows' => 8, 'rules' => ['required', 'string']],
                    ['name' => 'channels', 'label' => 'Channels', 'type' => 'json', 'rules' => ['nullable', 'string']],
                    ['name' => 'placeholders', 'label' => 'Placeholders', 'type' => 'json', 'rules' => ['nullable', 'string']],
                    ['name' => 'is_default', 'label' => 'Default Template', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
            'notifications' => [
                'table' => 'notifications',
                'label' => 'Notifications',
                'description' => 'Inspect and manually create in-app notification records.',
                'uuid_primary' => true,
                'order_by' => 'created_at',
                'order_direction' => 'desc',
                'search' => ['type', 'sender_type'],
                'columns' => ['notification_type_id', 'trigger_id', 'sender_type', 'notifiable_id', 'read_at'],
                'fields' => [
                    ['name' => 'notification_type_id', 'label' => 'Notification Type', 'type' => 'notification_type', 'rules' => ['nullable', 'integer', 'exists:notification_types,id']],
                    ['name' => 'trigger_id', 'label' => 'Trigger', 'type' => 'notification_trigger', 'rules' => ['nullable', 'integer', 'exists:notification_triggers,id']],
                    ['name' => 'sender_type', 'label' => 'Sender Type', 'type' => 'select', 'options' => ['system' => 'System', 'user' => 'User'], 'rules' => ['required', 'string', 'max:30']],
                    ['name' => 'sender_user_id', 'label' => 'Sender User', 'type' => 'user', 'rules' => ['nullable', 'integer', 'exists:users,id']],
                    ['name' => 'notifiable_id', 'label' => 'Recipient User', 'type' => 'user', 'rules' => ['required', 'integer', 'exists:users,id']],
                    ['name' => 'data', 'label' => 'Payload Data', 'type' => 'json', 'rules' => ['required', 'string']],
                    ['name' => 'recipients', 'label' => 'Recipients', 'type' => 'json', 'rules' => ['nullable', 'string']],
                    ['name' => 'action_link', 'label' => 'Action Link', 'type' => 'json', 'rules' => ['nullable', 'string']],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => ['info' => 'Info', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent', 'critical' => 'Critical'], 'rules' => ['nullable', 'string', 'max:20']],
                ],
            ],
            'notification-escalation-rules' => [
                'table' => 'notification_escalation_rules',
                'label' => 'Escalation Rules',
                'description' => 'Configure automated escalation workflows for inactivity, deadlines, and overdue items.',
                'order_by' => 'name',
                'search' => ['code', 'name', 'module', 'condition_type'],
                'columns' => ['code', 'name', 'module', 'condition_type', 'is_active'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'rules' => ['required', 'string', 'max:80'], 'unique' => true],
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['name' => 'module', 'label' => 'Module', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50']],
                    ['name' => 'condition_type', 'label' => 'Condition Type', 'type' => 'text', 'rules' => ['required', 'string', 'max:80']],
                    ['name' => 'condition_config', 'label' => 'Condition Config', 'type' => 'json', 'rules' => ['nullable', 'string']],
                    ['name' => 'escalation_steps', 'label' => 'Escalation Steps', 'type' => 'json', 'rules' => ['required', 'string']],
                    ['name' => 'cooldown_hours', 'label' => 'Cooldown Hours', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeNotificationStoreData(array $validated): array
    {
        $validated['type'] = 'database';
        $validated['notifiable_type'] = User::class;

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeJsonFieldValues(array $validated, array $config): array
    {
        foreach ($config['fields'] as $field) {
            if (($field['type'] ?? null) !== 'json') {
                continue;
            }

            $name = $field['name'];

            if (! array_key_exists($name, $validated) || ! is_string($validated[$name]) || $validated[$name] === '') {
                continue;
            }

            json_decode($validated[$name], true, 512, JSON_THROW_ON_ERROR);
        }

        return $validated;
    }

    private function profileCompletionFieldsResource(): array
    {
        $allowedKeys = implode(',', array_keys(ProfileCompletionField::definitions()));

        return [
            'table' => 'profile_completion_fields',
            'label' => 'Profile Completion Fields',
            'description' => 'Configure which member profile fields count toward dashboard completion.',
            'order_by' => 'sort_order',
            'search' => ['field_key', 'label'],
            'columns' => ['field_key', 'label', 'source', 'sort_order', 'is_active'],
            'fields' => [
                [
                    'name' => 'field_key',
                    'label' => 'Field Key',
                    'type' => 'select',
                    'options' => ProfileCompletionField::fieldKeyOptions(),
                    'rules' => ['required', 'string', 'max:60', "in:{$allowedKeys}"],
                    'unique' => true,
                ],
                ['name' => 'label', 'label' => 'Label', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                [
                    'name' => 'source',
                    'label' => 'Data Source',
                    'type' => 'select',
                    'options' => [
                        'user' => 'User account',
                        'profile' => 'Member profile',
                    ],
                    'rules' => ['required', 'string', 'in:user,profile'],
                ],
                ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0']],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
            ],
        ];
    }
}
