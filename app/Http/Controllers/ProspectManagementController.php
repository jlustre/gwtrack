<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectFunnel;
use App\Services\Prospects\ProspectAnalyticsService;
use App\Services\Prospects\ProspectExportService;
use App\Services\Prospects\ProspectFunnelService;
use App\Services\Prospects\ProspectShareService;
use App\Services\Prospects\ProspectTimelineService;
use App\Support\ProspectFormRules;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProspectManagementController extends Controller
{
    public function __construct(
        private ProspectFunnelService $funnels,
        private ProspectShareService $shares,
        private ProspectAnalyticsService $analytics,
        private ProspectTimelineService $timeline,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $ownProspects = Prospect::query()
            ->where('owner_id', $user->id)
            ->forSalesPipeline();
        $activeProspects = (clone $ownProspects)->where('status', 'active')->where('is_archived', false);
        $stats = $this->analytics->dashboardStatsFor($user);
        $pipelineFunnelId = (int) (ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('key', 'insurance')
            ->where('is_active', true)
            ->value('id') ?? $this->funnels->primaryFunnelIdFor($user));
        $pipelineSummary = $this->funnels->pipelineSummaryFor($user, $pipelineFunnelId);
        $pipelineSummaryGrouped = $this->funnels->pipelineSummaryGroupedFor($user, $pipelineFunnelId);
        $pipelineSummaryFunnel = ProspectFunnel::query()->find($pipelineFunnelId)?->name;

        $followUpsDueToday = DB::table('prospect_followups')
            ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
            ->where('prospect_followups.assigned_user_id', $user->id)
            ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
            ->whereIn('prospect_followups.status', ['pending', 'overdue'])
            ->whereDate('prospect_followups.due_at', '<=', now()->toDateString())
            ->whereNull('prospect_followups.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderBy('prospect_followups.due_at')
            ->limit(8)
            ->get([
                'prospect_followups.id',
                'prospect_followups.followup_type',
                'prospect_followups.priority',
                'prospect_followups.status',
                'prospect_followups.due_at',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'prospects.interest_level',
            ]);

        $upcomingAppointments = DB::table('prospect_appointments')
            ->join('prospects', 'prospects.id', '=', 'prospect_appointments.prospect_id')
            ->leftJoin('appointment_types', 'appointment_types.id', '=', 'prospect_appointments.appointment_type_id')
            ->leftJoin('users as helpers', 'helpers.id', '=', 'prospect_appointments.assigned_helper_id')
            ->where('prospect_appointments.owner_id', $user->id)
            ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
            ->where('prospect_appointments.status', 'scheduled')
            ->where('prospect_appointments.scheduled_at', '>=', now())
            ->whereNull('prospect_appointments.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderBy('prospect_appointments.scheduled_at')
            ->limit(6)
            ->get([
                'prospect_appointments.id',
                'prospect_appointments.scheduled_at',
                'prospect_appointments.purpose',
                'prospect_appointments.location_or_link',
                'appointment_types.name as appointment_type',
                'helpers.name as helper_name',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
            ]);

        $hotProspects = (clone $activeProspects)
            ->with(['source:id,name', 'stage:id,name'])
            ->where('interest_level', 'hot')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('next_follow_up_at')
            ->limit(6)
            ->get();

        $recentCommunications = DB::table('prospect_communications')
            ->join('prospects', 'prospects.id', '=', 'prospect_communications.prospect_id')
            ->leftJoin('communication_types', 'communication_types.id', '=', 'prospect_communications.communication_type_id')
            ->where('prospects.owner_id', $user->id)
            ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
            ->whereNull('prospect_communications.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderByDesc('prospect_communications.contacted_at')
            ->limit(6)
            ->get([
                'prospect_communications.id',
                'prospect_communications.direction',
                'prospect_communications.outcome',
                'prospect_communications.next_action',
                'prospect_communications.contacted_at',
                'communication_types.name as communication_type',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
            ]);

        $recentlyContactedProspects = (clone $activeProspects)
            ->whereNotNull('last_contacted_at')
            ->orderByDesc('last_contacted_at')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'last_contacted_at', 'next_follow_up_at', 'interest_level']);

        $prospectsTable = (clone $activeProspects)
            ->with(['source:id,name', 'stage:id,name'])
            ->latest()
            ->limit(12)
            ->get();

        $followUpsTable = DB::table('prospect_followups')
            ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
            ->where('prospect_followups.assigned_user_id', $user->id)
            ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
            ->whereNull('prospect_followups.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderBy('prospect_followups.due_at')
            ->limit(12)
            ->get([
                'prospect_followups.id',
                'prospect_followups.followup_type',
                'prospect_followups.priority',
                'prospect_followups.status',
                'prospect_followups.due_at',
                'prospect_followups.notes',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'prospects.email',
                'prospects.phone',
            ]);

        $appointmentsTable = DB::table('prospect_appointments')
            ->join('prospects', 'prospects.id', '=', 'prospect_appointments.prospect_id')
            ->leftJoin('appointment_types', 'appointment_types.id', '=', 'prospect_appointments.appointment_type_id')
            ->leftJoin('users as helpers', 'helpers.id', '=', 'prospect_appointments.assigned_helper_id')
            ->where('prospect_appointments.owner_id', $user->id)
            ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
            ->whereNull('prospect_appointments.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderByDesc('prospect_appointments.scheduled_at')
            ->limit(12)
            ->get([
                'prospect_appointments.id',
                'prospect_appointments.scheduled_at',
                'prospect_appointments.purpose',
                'prospect_appointments.status',
                'prospect_appointments.location_or_link',
                'appointment_types.name as appointment_type',
                'helpers.name as helper_name',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
            ]);

        $sharedWithMe = DB::table('prospect_shares')
            ->join('prospects', 'prospects.id', '=', 'prospect_shares.prospect_id')
            ->join('users as owners', 'owners.id', '=', 'prospect_shares.granted_by')
            ->leftJoin('prospect_share_permissions', 'prospect_share_permissions.id', '=', 'prospect_shares.prospect_share_permission_id')
            ->where('prospect_shares.shared_with', $user->id)
            ->where('prospect_shares.status', 'active')
            ->whereNull('prospect_shares.revoked_at')
            ->whereNull('prospect_shares.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('prospect_shares.expires_at')->orWhere('prospect_shares.expires_at', '>', now());
            })
            ->orderByDesc('prospect_shares.granted_at')
            ->limit(5)
            ->get([
                'prospect_shares.id',
                'prospect_shares.granted_at',
                'prospect_shares.expires_at',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'owners.name as owner_name',
                'prospect_share_permissions.name as permission_name',
            ]);

        $sharedByMe = DB::table('prospect_shares')
            ->join('prospects', 'prospects.id', '=', 'prospect_shares.prospect_id')
            ->join('users as collaborators', 'collaborators.id', '=', 'prospect_shares.shared_with')
            ->leftJoin('prospect_share_permissions', 'prospect_share_permissions.id', '=', 'prospect_shares.prospect_share_permission_id')
            ->where('prospect_shares.granted_by', $user->id)
            ->where('prospect_shares.status', 'active')
            ->whereNull('prospect_shares.revoked_at')
            ->whereNull('prospect_shares.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderByDesc('prospect_shares.granted_at')
            ->limit(5)
            ->get([
                'prospect_shares.id',
                'prospect_shares.granted_at',
                'prospect_shares.expires_at',
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'collaborators.name as collaborator_name',
                'prospect_share_permissions.name as permission_name',
            ]);

        $recentImport = DB::table('prospect_imports')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        $importsTable = DB::table('prospect_imports')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $sourcePerformance = DB::table('prospect_sources')
            ->leftJoin('prospects', function ($join) use ($user): void {
                $join->on('prospects.prospect_source_id', '=', 'prospect_sources.id')
                    ->where('prospects.owner_id', '=', $user->id)
                    ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                    ->whereNull('prospects.deleted_at');
            })
            ->where('prospect_sources.is_active', true)
            ->select(['prospect_sources.name', DB::raw('COUNT(prospects.id) as prospect_count')])
            ->groupBy('prospect_sources.id', 'prospect_sources.name')
            ->orderByDesc('prospect_count')
            ->limit(5)
            ->get();

        $allProspects = Prospect::query()
            ->with(['source:id,name', 'stage:id,name'])
            ->where('owner_id', $user->id)
            ->forSalesPipeline()
            ->when($request->filled('prospect_search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('prospect_search'));

                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('prospect_status'), fn ($query) => $query->where('status', $request->string('prospect_status')))
            ->when($request->filled('prospect_stage'), fn ($query) => $query->where('pipeline_stage_id', $request->integer('prospect_stage')))
            ->when($request->filled('prospect_source'), fn ($query) => $query->where('prospect_source_id', $request->integer('prospect_source')))
            ->when($request->filled('prospect_interest'), fn ($query) => $query->where('interest_level', $request->string('prospect_interest')))
            ->when($request->filled('prospect_priority'), fn ($query) => $query->where('priority', $request->string('prospect_priority')))
            ->when($request->filled('prospect_funnel'), fn ($query) => $query->where('funnel_type', $request->string('prospect_funnel')))
            ->when($request->boolean('prospect_converted'), fn ($query) => $query->whereNotNull('converted_to'))
            ->orderByDesc('updated_at')
            ->paginate(10, ['*'], 'prospects_page')
            ->withQueryString();

        return view('team.prospects', [
            'stats' => $stats,
            'pipelineStages' => DB::table('pipeline_stages')->whereNull('user_id')->where('is_active', true)->orderBy('sort_order')->get(),
            'pipelineSummary' => $pipelineSummary,
            'pipelineSummaryGrouped' => $pipelineSummaryGrouped,
            'pipelineFunnelId' => $pipelineFunnelId,
            'pipelineSummaryFunnel' => $pipelineSummaryFunnel,
            'allProspects' => $allProspects,
            'prospectStatuses' => (clone $ownProspects)->select('status')->distinct()->orderBy('status')->pluck('status'),
            'prospectSources' => DB::table('prospect_sources')->where('is_active', true)->orderBy('sort_order')->get(),
            'prospectTypes' => DB::table('prospect_types')->where('is_active', true)->orderBy('sort_order')->limit(8)->get(),
            'interests' => DB::table('prospect_interests')->where('is_active', true)->orderBy('sort_order')->limit(8)->get(),
            'followUpsDueToday' => $followUpsDueToday,
            'upcomingAppointments' => $upcomingAppointments,
            'hotProspects' => $hotProspects,
            'recentCommunications' => $recentCommunications,
            'recentlyContactedProspects' => $recentlyContactedProspects,
            'prospectsTable' => $prospectsTable,
            'followUpsTable' => $followUpsTable,
            'appointmentsTable' => $appointmentsTable,
            'sharedWithMe' => $sharedWithMe,
            'sharedByMe' => $sharedByMe,
            'recentImport' => $recentImport,
            'importsTable' => $importsTable,
            'sourcePerformance' => $sourcePerformance,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Prospect::class);

        return view('team.prospect-create');
    }

    public function pipeline(): View|RedirectResponse
    {
        $this->authorize('viewAny', Prospect::class);

        if (request()->query('funnel') === 'recruiting') {
            return redirect()->route('team.recruiting.index');
        }

        return view('team.prospect-pipeline');
    }

    public function pipelineStageProspects(Request $request, int $stage): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);

        $user = $request->user();
        $funnelId = $request->filled('funnel_id')
            ? $request->integer('funnel_id')
            : $this->funnels->primaryFunnelIdFor($user);

        $prospects = Prospect::query()
            ->where('owner_id', $user->id)
            ->where('prospect_funnel_id', $funnelId)
            ->where('pipeline_stage_id', $stage)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'preferred_name', 'email', 'phone', 'interest_level', 'status']);

        return response()->json([
            'stage_label' => $this->funnels->stageLabelForFunnel($funnelId, $stage),
            'prospects' => $prospects->map(fn (Prospect $prospect): array => [
                'id' => $prospect->id,
                'name' => $prospect->displayName(),
                'email' => $prospect->email,
                'phone' => $prospect->phone,
                'interest_level' => str($prospect->interest_level)->title(),
                'status' => str($prospect->status)->title(),
                'profile_url' => route('team.prospects.records.show', $prospect),
            ])->values(),
        ]);
    }

    public function followUps(): View
    {
        $this->authorize('viewAny', Prospect::class);

        return view('team.prospect-follow-ups');
    }

    public function appointments(): View
    {
        $this->authorize('viewAny', Prospect::class);

        return view('team.prospect-appointments');
    }

    public function accessManager(): View
    {
        $this->authorize('viewAny', Prospect::class);

        return view('team.prospect-access-manager');
    }

    public function sharedWithMe(): RedirectResponse
    {
        $this->authorize('viewAny', Prospect::class);

        return redirect()->to(route('team.prospects.screen', 'prospects').'#shared-with-me');
    }

    public function sharedByMe(): RedirectResponse
    {
        $this->authorize('viewAny', Prospect::class);

        return redirect()->to(route('team.prospects.screen', 'prospects').'#shared-by-me');
    }

    public function analytics(): View
    {
        $this->authorize('viewAny', Prospect::class);

        return view('team.prospect-analytics');
    }

    public function aiCoach(): View
    {
        $this->authorize('viewAny', Prospect::class);

        return view('team.prospect-ai-coach');
    }

    public function import(): View
    {
        abort_unless(auth()->user()?->can('import prospects'), 403);

        return view('team.prospect-import');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('import prospects'), 403);

        $path = resource_path('templates/prospect-import-template.csv');

        abort_unless(is_file($path), 404);

        return response()->streamDownload(function () use ($path): void {
            echo "\xEF\xBB\xBF";
            readfile($path);
        }, 'prospect-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(Request $request, ProspectExportService $exports): StreamedResponse
    {
        $this->authorize('viewAny', Prospect::class);

        return $exports->streamForUser($request->user());
    }

    public function placeholder(Request $request, string $screen): View
    {
        abort_unless(in_array($screen, [
            'prospects',
            'activity-logs',
            'settings',
        ], true), 404);

        return view('team.prospect-screen', [
            'screen' => str($screen)->replace('-', ' ')->title(),
            'screenKey' => $screen,
            ...$this->screenData($request),
        ]);
    }

    public function show(Request $request, Prospect $prospect): View
    {
        $this->authorize('view', $prospect);

        $user = $request->user();

        if ((int) $prospect->owner_id !== $user->id) {
            $this->shares->logAccess($prospect, $user, 'view');
        }

        return view('team.prospect-record', [
            'prospect' => $prospect->load([
                'source:id,name',
                'stage:id,name',
                'funnel:id,name,key',
                'types:id,name',
                'interests:id,name',
                'tags:id,name',
                'conversions.convertedBy:id,name',
                'conversions.createdUser:id,name',
            ]),
            'relationshipStats' => $this->timeline->statsFor($prospect),
        ]);
    }

    public function activity(Request $request, Prospect $prospect): View
    {
        $this->authorize('view', $prospect);

        $user = $request->user();

        if ((int) $prospect->owner_id !== $user->id) {
            $this->shares->logAccess($prospect, $user, 'view');
        }

        return view('team.prospect-activity', [
            'prospect' => $prospect->load([
                'source:id,name',
                'stage:id,name',
                'funnel:id,name,key',
            ]),
        ]);
    }

    public function edit(Prospect $prospect): View
    {
        $this->authorize('update', $prospect);

        $prospect = $prospect->load(['source:id,name', 'stage:id,name', 'funnel:id,name,key']);
        $prospectFunnelId = $this->resolveProspectFunnelId($prospect);

        return view('team.prospect-edit', [
            'prospect' => $prospect,
            'prospectFunnelId' => $prospectFunnelId,
            'funnelTypes' => config('prospects.funnel_types'),
            'fnaStatuses' => config('prospects.fna_statuses'),
            'sources' => DB::table('prospect_sources')->where('is_active', true)->orderBy('sort_order')->get(),
            'stages' => $this->funnels->numberedStagesForFunnel($prospectFunnelId),
        ]);
    }

    public function update(Request $request, Prospect $prospect): RedirectResponse
    {
        $this->authorize('update', $prospect);

        $validated = ProspectFormRules::normalizeProfileAttributes($request->validate(array_merge([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'home_phone' => ['nullable', 'string', 'max:60'],
            'work_phone' => ['nullable', 'string', 'max:60'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'funnel_type' => ['required', 'in:insurance,recruiting,both'],
            'prospect_funnel_id' => ['nullable', 'exists:prospect_funnels,id'],
            'prospect_source_id' => ['nullable', 'exists:prospect_sources,id'],
            'pipeline_stage_id' => ['nullable', 'exists:pipeline_stages,id'],
            'status' => ['required', 'string', 'max:60'],
            'interest_level' => ['required', 'in:cold,warm,hot'],
            'interest_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'fna_status' => ['nullable', 'string', 'max:40'],
            'referral_source_name' => ['nullable', 'string', 'max:255'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'next_follow_up_at' => ['nullable', 'date'],
        ], ProspectFormRules::profileRules())));

        try {
            $validated['prospect_funnel_id'] = $this->funnels->resolveFunnel(
                $validated['funnel_type'],
                $validated['prospect_funnel_id'] ?? null,
            )->id;
        } catch (ModelNotFoundException) {
            unset($validated['prospect_funnel_id'], $validated['funnel_type']);
        }

        $this->funnels->updateProspect($prospect, $request->user(), $validated);

        return redirect()
            ->route('team.prospects.records.show', $prospect)
            ->with('status', 'Prospect updated successfully.');
    }

    public function archive(Prospect $prospect): RedirectResponse
    {
        $this->authorize('update', $prospect);

        $prospect->update([
            'status' => 'archived',
            'is_archived' => true,
            'archived_at' => now(),
        ]);
        $this->funnels->syncRelationships($prospect);

        return redirect()
            ->route('team.prospects')
            ->with('status', 'Prospect archived.');
    }

    public function restore(Prospect $prospect): RedirectResponse
    {
        $this->authorize('update', $prospect);

        $prospect->update([
            'status' => 'active',
            'is_archived' => false,
            'archived_at' => null,
        ]);
        $this->funnels->syncRelationships($prospect);

        return back()->with('status', 'Prospect restored.');
    }

    public function destroy(Prospect $prospect): RedirectResponse
    {
        $this->authorize('delete', $prospect);

        $prospect->delete();

        return redirect()
            ->route('team.prospects')
            ->with('status', 'Prospect deleted.');
    }

    private function screenData(Request $request): array
    {
        $user = $request->user();

        return [
            'pipelineStages' => DB::table('pipeline_stages')->whereNull('user_id')->where('is_active', true)->orderBy('sort_order')->get(),
            'sources' => DB::table('prospect_sources')->where('is_active', true)->orderBy('sort_order')->get(),
            'types' => DB::table('prospect_types')->where('is_active', true)->orderBy('sort_order')->get(),
            'interests' => DB::table('prospect_interests')->where('is_active', true)->orderBy('sort_order')->get(),
            'prospects' => Prospect::query()
                ->with(['source:id,name', 'stage:id,name'])
                ->where('owner_id', $user->id)
                ->forSalesPipeline()
                ->whereNull('deleted_at')
                ->latest()
                ->limit(25)
                ->get(),
            'allProspects' => Prospect::query()
                ->with(['source:id,name', 'stage:id,name'])
                ->where('owner_id', $user->id)
                ->forSalesPipeline()
                ->when($request->filled('prospect_search'), function ($query) use ($request): void {
                    $search = trim((string) $request->string('prospect_search'));

                    $query->where(function ($query) use ($search): void {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('prospect_status'), fn ($query) => $query->where('status', $request->string('prospect_status')))
                ->when($request->filled('prospect_stage'), fn ($query) => $query->where('pipeline_stage_id', $request->integer('prospect_stage')))
                ->when($request->filled('prospect_source'), fn ($query) => $query->where('prospect_source_id', $request->integer('prospect_source')))
                ->when($request->filled('prospect_interest'), fn ($query) => $query->where('interest_level', $request->string('prospect_interest')))
                ->when($request->filled('prospect_priority'), fn ($query) => $query->where('priority', $request->string('prospect_priority')))
                ->when($request->filled('prospect_funnel'), fn ($query) => $query->where('funnel_type', $request->string('prospect_funnel')))
                ->orderByDesc('updated_at')
                ->paginate(10, ['*'], 'prospects_page')
                ->withQueryString(),
            'prospectStatuses' => Prospect::query()
                ->where('owner_id', $user->id)
                ->select('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),
            'prospectSources' => DB::table('prospect_sources')->where('is_active', true)->orderBy('sort_order')->get(),
            'followUps' => DB::table('prospect_followups')
                ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
                ->where('prospect_followups.assigned_user_id', $user->id)
                ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                ->whereNull('prospect_followups.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->orderBy('prospect_followups.due_at')
                ->limit(25)
                ->get([
                    'prospect_followups.id',
                    'prospect_followups.followup_type',
                    'prospect_followups.priority',
                    'prospect_followups.status',
                    'prospect_followups.due_at',
                    'prospect_followups.notes',
                    'prospects.first_name',
                    'prospects.last_name',
                    'prospects.email',
                    'prospects.phone',
                ]),
            'appointments' => DB::table('prospect_appointments')
                ->join('prospects', 'prospects.id', '=', 'prospect_appointments.prospect_id')
                ->leftJoin('appointment_types', 'appointment_types.id', '=', 'prospect_appointments.appointment_type_id')
                ->leftJoin('users as helpers', 'helpers.id', '=', 'prospect_appointments.assigned_helper_id')
                ->where('prospect_appointments.owner_id', $user->id)
                ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                ->whereNull('prospect_appointments.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->orderByDesc('prospect_appointments.scheduled_at')
                ->limit(25)
                ->get([
                    'prospect_appointments.id',
                    'prospect_appointments.scheduled_at',
                    'prospect_appointments.purpose',
                    'prospect_appointments.status',
                    'prospect_appointments.location_or_link',
                    'appointment_types.name as appointment_type',
                    'helpers.name as helper_name',
                    'prospects.first_name',
                    'prospects.last_name',
                ]),
            'activities' => DB::table('prospect_activities')
                ->join('prospects', 'prospects.id', '=', 'prospect_activities.prospect_id')
                ->where('prospects.owner_id', $user->id)
                ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                ->whereNull('prospect_activities.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->orderByDesc('prospect_activities.occurred_at')
                ->limit(25)
                ->get([
                    'prospect_activities.id',
                    'prospect_activities.activity_type',
                    'prospect_activities.occurred_at',
                    'prospect_activities.duration_minutes',
                    'prospect_activities.outcome',
                    'prospect_activities.next_action',
                    'prospects.id as prospect_id',
                    'prospects.first_name',
                    'prospects.last_name',
                    'prospects.email',
                    'prospects.phone',
                ]),
            'shares' => DB::table('prospect_shares')
                ->join('prospects', 'prospects.id', '=', 'prospect_shares.prospect_id')
                ->join('users as collaborators', 'collaborators.id', '=', 'prospect_shares.shared_with')
                ->leftJoin('prospect_share_permissions', 'prospect_share_permissions.id', '=', 'prospect_shares.prospect_share_permission_id')
                ->where('prospect_shares.granted_by', $user->id)
                ->whereNull('prospect_shares.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->orderByDesc('prospect_shares.granted_at')
                ->limit(25)
                ->get([
                    'prospect_shares.id',
                    'prospect_shares.status',
                    'prospect_shares.granted_at',
                    'prospect_shares.expires_at',
                    'prospects.first_name',
                    'prospects.last_name',
                    'collaborators.name as collaborator_name',
                    'prospect_share_permissions.name as permission_name',
                ]),
            'sharedWithMe' => DB::table('prospect_shares')
                ->join('prospects', 'prospects.id', '=', 'prospect_shares.prospect_id')
                ->join('users as owners', 'owners.id', '=', 'prospect_shares.granted_by')
                ->leftJoin('prospect_share_permissions', 'prospect_share_permissions.id', '=', 'prospect_shares.prospect_share_permission_id')
                ->where('prospect_shares.shared_with', $user->id)
                ->where('prospect_shares.status', 'active')
                ->whereNull('prospect_shares.revoked_at')
                ->whereNull('prospect_shares.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->where(function ($query): void {
                    $query->whereNull('prospect_shares.expires_at')->orWhere('prospect_shares.expires_at', '>', now());
                })
                ->orderByDesc('prospect_shares.granted_at')
                ->limit(25)
                ->get([
                    'prospect_shares.id',
                    'prospect_shares.granted_at',
                    'prospect_shares.expires_at',
                    'prospects.first_name',
                    'prospects.last_name',
                    'owners.name as owner_name',
                    'prospect_share_permissions.name as permission_name',
                ]),
            'imports' => DB::table('prospect_imports')
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(25)
                ->get(),
        ];
    }

    private function resolveProspectFunnelId(Prospect $prospect): ?int
    {
        if ($prospect->prospect_funnel_id) {
            return (int) $prospect->prospect_funnel_id;
        }

        try {
            return $this->funnels->resolveFunnel($prospect->funnel_type ?? 'insurance')->id;
        } catch (ModelNotFoundException) {
            return null;
        }
    }
}
