<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AdminTrainingController;
use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssociateParticipationAgreementController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CfmAssignmentConfirmationController;
use App\Http\Controllers\CfmEffectivenessReportController;
use App\Http\Controllers\CfmManagementController;
use App\Http\Controllers\CfmPortalController;
use App\Http\Controllers\CfmTraineeChecklistController;
use App\Http\Controllers\ChecklistTypeStartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownlineController;
use App\Http\Controllers\FnaManagementController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\GoalReportController;
use App\Http\Controllers\LeadershipReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationDeviceTokenController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProspectActivityController;
use App\Http\Controllers\ProspectManagementController;
use App\Http\Controllers\ResourceDocumentsController;
use App\Http\Controllers\ResourceHubController;
use App\Http\Controllers\ResourceLinksController;
use App\Http\Controllers\ResourceVideosController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TrackerChecklistController;
use App\Http\Controllers\TrainingAchievementsController;
use App\Http\Controllers\TrainingAssignmentController;
use App\Http\Controllers\TrainingCertificationController;
use App\Http\Controllers\TrainingCoachingController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingPathController;
use App\Http\Controllers\TrainingPlanController;
use App\Http\Controllers\TrainingReportController;
use App\Http\Controllers\TrainingSessionController;
use App\Livewire\Activities\DailyActivitiesPage;
use App\Livewire\Admin\AdminSettingsPage;
use App\Livewire\Admin\Backups\BackupDashboard;
use App\Livewire\Admin\Backups\BackupHistory;
use App\Livewire\Admin\Backups\CreateBackup;
use App\Livewire\Admin\Backups\RestoreBackup;
use App\Livewire\Admin\Backups\RestoreHistory;
use App\Livewire\Admin\Cfm\CertificationReviewManager;
use App\Livewire\Admin\Cfm\CfmPerformanceManager;
use App\Livewire\Admin\Crm\PipelineManager;
use App\Livewire\Admin\RankAdministrationManager;
use App\Livewire\Admin\Roles\RolesPermissionsManager;
use App\Livewire\Admin\SubscriptionPaymentsPage;
use App\Livewire\Booking\BookingsHub;
use App\Livewire\Cfm\Portal;
use App\Livewire\Communication\AnnouncementArchive;
use App\Livewire\Communication\AnnouncementBookmarks;
use App\Livewire\Communication\AnnouncementComposer;
use App\Livewire\Communication\AnnouncementShow;
use App\Livewire\Communication\CampaignCenter;
use App\Livewire\Communication\CampaignComposer;
use App\Livewire\Communication\CampaignShow;
use App\Livewire\Communication\CommunicationHub;
use App\Livewire\Communication\EventAnnouncementComposer;
use App\Livewire\Communication\LeadershipDesk;
use App\Livewire\Communication\RecognitionCenter;
use App\Livewire\Communication\RecognitionComposer;
use App\Livewire\ComplianceLifecycleHub;
use App\Livewire\Crm\ConsultantTracker;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\FunnelDashboard;
use App\Livewire\Crm\PipelineComparison;
use App\Livewire\Fna\Client\FnaClientPortalGate;
use App\Livewire\Fna\Client\FnaClientPortalReturn;
use App\Livewire\Fna\Client\FnaClientPortalWizard;
use App\Livewire\Goals\GoalsHub;
use App\Livewire\Members\MyInvitesPage;
use App\Livewire\Messaging\MessageCenter;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Notifications\NotificationPreferences;
use App\Livewire\Team\MyTrainees;
use App\Livewire\Trackers\TrackerChecklistPage;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\GoalBlueprint;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/run-migrations-secret-2026', function () {
    abort_unless(app()->environment(['staging']), 403);

    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--force' => true]);

    return nl2br(Artisan::output());
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book/invite/{token}', [BookingController::class, 'invite'])->name('bookings.invite');
Route::get('/book/mentor/{mentorSlug}', [BookingController::class, 'publicPage'])->name('bookings.mentor');
Route::get('/book/{username}/{eventTypeSlug?}', [BookingController::class, 'publicPage'])->name('bookings.public');

Route::prefix('fna/client')->name('fna.client.')->group(function (): void {
    Route::get('/invite/{token}', FnaClientPortalGate::class)->name('invite');
    Route::get('/invite/{token}/wizard', FnaClientPortalWizard::class)->name('wizard');
    Route::get('/return', FnaClientPortalReturn::class)->name('return');
});

Route::get('/cfm/assignments/{assignment}/confirm', [CfmAssignmentConfirmationController::class, 'show'])
    ->middleware('signed')
    ->name('cfm.assignments.confirm');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');
    Route::get('/dashboard/stats/{type}/members', [DashboardController::class, 'statDetails'])
        ->middleware('permission:view dashboard')
        ->name('dashboard.stat-details');
    Route::view('/reports/leadership', 'reports.leadership')
        ->middleware('permission:view leadership reports')
        ->name('reports.leadership');
    Route::get('/reports/leadership/download', [LeadershipReportController::class, 'download'])
        ->middleware('permission:view leadership reports')
        ->name('reports.leadership.download');

    Route::get('/messages', MessageCenter::class)->middleware('permission:view conversations')->name('messages.index');

    Route::get('/search', [GlobalSearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggest', [GlobalSearchController::class, 'suggest'])->name('search.suggest');
    Route::get('/onboarding', TrackerChecklistPage::class)->name('onboarding.index');
    Route::patch('/onboarding/{step}', [OnboardingController::class, 'update'])->name('onboarding.update');
    Route::patch('/onboarding-progress/{progress}/review', [OnboardingController::class, 'review'])->name('onboarding.review');
    Route::get('/licensing', TrackerChecklistPage::class)->name('licensing.index');
    Route::patch('/licensing/{step}', [TrackerChecklistController::class, 'updateLicensing'])->name('licensing.update');
    Route::patch('/licensing-progress/{progress}/review', [TrackerChecklistController::class, 'reviewLicensing'])->name('licensing.review');
    Route::get('/contracting', TrackerChecklistPage::class)->name('contracting.index');
    Route::patch('/contracting/{step}', [TrackerChecklistController::class, 'updateContracting'])->name('contracting.update');
    Route::patch('/contracting-progress/{progress}/review', [TrackerChecklistController::class, 'reviewContracting'])->name('contracting.review');
    Route::get('/compliance', ComplianceLifecycleHub::class)->name('compliance.index');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks/{taskUser}/comments', [TaskController::class, 'storeComment'])->name('tasks.comments.store');
    Route::get('/invites', MyInvitesPage::class)->name('invites.index');
    Route::get('/daily-activities', DailyActivitiesPage::class)->name('daily-activities.index');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::patch('/billing/plan', [BillingController::class, 'updatePlan'])->name('billing.plan.update');
    Route::get('/billing/invoices/{invoice}', [BillingController::class, 'show'])->name('billing.invoices.show');
    Route::get('/billing/invoices/{invoice}/pay', [BillingController::class, 'pay'])->name('billing.invoices.pay');
    Route::post('/billing/invoices/{invoice}/pay', [BillingController::class, 'processPayment'])->name('billing.invoices.pay.store');

    Route::prefix('crm')
        ->name('crm.')
        ->middleware('permission:view crm funnels')
        ->group(function (): void {
            Route::get('/', FunnelDashboard::class)->name('index');
            Route::get('/consultants', ConsultantTracker::class)->name('consultants');
            Route::get('/pipelines/compare', PipelineComparison::class)->name('pipelines.compare');
            Route::get('/funnels/{pipeline}', FunnelBoard::class)->name('funnels.show');
        });
    Route::get('/field-apprenticeship', TrackerChecklistPage::class)->name('apprenticeship.index');
    Route::patch('/field-apprenticeship/{step}', [TrackerChecklistController::class, 'updateApprenticeship'])->name('apprenticeship.update');
    Route::patch('/field-apprenticeship-progress/{progress}/review', [TrackerChecklistController::class, 'reviewApprenticeship'])->name('apprenticeship.review');
    Route::post('/checklists/{typeCode}/start', [ChecklistTypeStartController::class, 'storeSelf'])->name('checklists.type.start');
    Route::get('/cfm-training', TrackerChecklistPage::class)->name('cfm-training.index');
    Route::patch('/cfm-training/{step}', [TrackerChecklistController::class, 'updateCfmTraining'])->name('cfm-training.update');
    Route::patch('/cfm-training-progress/{progress}/review', [TrackerChecklistController::class, 'reviewCfmTraining'])->name('cfm-training.review');
    Route::get('/cfm/portal', Portal::class)->middleware('cfm.portal')->name('cfm.portal');
    Route::get('/cfm/portal/reports/{report}/download', [CfmPortalController::class, 'downloadReport'])->middleware('cfm.portal')->name('cfm.portal.reports.download');
    Route::get('/cfm/portal/roster/export', [CfmPortalController::class, 'exportRoster'])->middleware('cfm.portal')->name('cfm.portal.roster.export');
    Route::patch('/cfm/portal/profile', [CfmPortalController::class, 'updateProfile'])->middleware('cfm.portal')->name('cfm.portal.profile.update');
    Route::patch('/cfm/portal/calendar-sharing', [CfmPortalController::class, 'updateCalendarSharing'])->middleware('cfm.portal')->name('cfm.portal.calendar-sharing.update');
    Route::get('/cfm/portal/assignments/{assignment}/review', [CfmPortalController::class, 'reviewAssignment'])->middleware('cfm.portal')->name('cfm.portal.assignments.review');
    Route::post('/cfm/portal/assignments/{assignment}/confirm', [CfmPortalController::class, 'confirmAssignment'])->middleware('cfm.portal')->name('cfm.portal.assignments.confirm');
    Route::post('/cfm/portal/assignments/{assignment}/decline', [CfmAssignmentConfirmationController::class, 'decline'])->middleware('cfm.portal')->name('cfm.portal.assignments.decline');
    Route::post('/cfm/portal/assignments/{assignment}/first-contact', [CfmPortalController::class, 'sendFirstContact'])->middleware('cfm.portal')->name('cfm.portal.assignments.first-contact');
    Route::get('/cfm/portal/trainees/{assignment}/checklist', [CfmTraineeChecklistController::class, 'show'])->middleware('cfm.portal')->name('cfm.portal.trainees.checklist');
    Route::patch('/cfm/portal/trainees/{assignment}/checklist/{item}', [CfmTraineeChecklistController::class, 'update'])->middleware('cfm.portal')->name('cfm.portal.trainees.checklist.update');

    Route::prefix('cfm/effectiveness')->name('cfm.effectiveness.')->group(function () {
        Route::view('/', 'cfm-effectiveness.index')->middleware('permission:view CFM effectiveness')->name('index');
        Route::view('/reviews', 'cfm-effectiveness.reviews.index')->middleware('permission:view own mentor feedback requests')->name('reviews');
        Route::get('/reviews/{review}', fn (CfmReview $review) => view('cfm-effectiveness.reviews.show', compact('review')))
            ->middleware('permission:submit mentor feedback')
            ->name('reviews.show');
        Route::view('/evaluations', 'cfm-effectiveness.evaluations')->middleware('permission:manage CFM evaluations')->name('evaluations');
        Route::view('/improvement', 'cfm-effectiveness.improvement')->middleware('permission:view CFM effectiveness')->name('improvement');
        Route::view('/leaderboard', 'cfm-effectiveness.leaderboard')->middleware('permission:view CFM effectiveness')->name('leaderboard');
        Route::view('/recognition', 'cfm-effectiveness.recognition')->middleware('permission:view CFM effectiveness')->name('recognition');
        Route::view('/reports', 'cfm-effectiveness.reports')->middleware('permission:view CFM effectiveness|view CFM reports')->name('reports');
        Route::get('/reports/{report}/download', [CfmEffectivenessReportController::class, 'download'])
            ->middleware('permission:view CFM effectiveness|view CFM reports')
            ->name('reports.download');
        Route::post('/reports/generate', [CfmEffectivenessReportController::class, 'generate'])
            ->middleware('permission:view CFM effectiveness|view CFM reports')
            ->name('reports.generate');
    });
    Route::view('/training', 'training.index')->name('training.index');
    Route::get('/training/courses/{module:slug}', [TrainingController::class, 'course'])->name('training.courses.show');
    Route::get('/training/courses/{module:slug}/lessons/{lesson}', [TrainingController::class, 'lesson'])->name('training.lessons.show');
    Route::get('/training/assignments', [TrainingAssignmentController::class, 'index'])->name('training.assignments.index');
    Route::get('/training/assignments/manage', [TrainingAssignmentController::class, 'manage'])->name('training.assignments.manage')->middleware('permission:manage training');
    Route::get('/training/certifications', [TrainingCertificationController::class, 'index'])->name('training.certifications.index');
    Route::get('/training/certifications/reviews', [TrainingCertificationController::class, 'reviews'])->name('training.certifications.reviews');
    Route::get('/training/certifications/{userCertification}', [TrainingCertificationController::class, 'show'])->name('training.certifications.show');
    Route::get('/training/paths', [TrainingPathController::class, 'index'])->name('training.paths.index');
    Route::get('/training/paths/{path:code}', [TrainingPathController::class, 'show'])->name('training.paths.show');
    Route::get('/training/coaching', [TrainingCoachingController::class, 'index'])->name('training.coaching.index');
    Route::get('/training/sessions', [TrainingSessionController::class, 'index'])->name('training.sessions.index');
    Route::get('/training/sessions/{session}', [TrainingSessionController::class, 'show'])->name('training.sessions.show');
    Route::get('/training/achievements', [TrainingAchievementsController::class, 'index'])->name('training.achievements.index');
    Route::get('/training/plan', [TrainingPlanController::class, 'index'])->name('training.plan.index');
    Route::get('/training/reports', [TrainingReportController::class, 'index'])->name('training.reports.index');
    Route::get('/training/reports/download', [TrainingReportController::class, 'download'])->name('training.reports.download');
    Route::post('/training/reports/email', [TrainingReportController::class, 'email'])->name('training.reports.email');
    Route::get('/goals', GoalsHub::class)->middleware('permission:manage goals')->name('goals.index');
    Route::get('/goals/plan', fn () => redirect()->route('goals.index', ['tab' => 'plan']))->middleware('permission:manage goals')->name('goals.plan');
    Route::get('/goals/create', fn () => redirect()->route('goals.index', array_filter(['tab' => 'create', 'template' => request('template')])))->middleware('permission:manage goals')->name('goals.create');
    Route::get('/goals/blueprint/{blueprint}', fn (GoalBlueprint $blueprint) => view('goals.blueprint.show', compact('blueprint')))->middleware('permission:manage goals')->name('goals.blueprint.show');
    Route::get('/goals/what-if', fn () => redirect()->route('goals.index', ['tab' => 'what-if']))->middleware('permission:manage goals')->name('goals.what-if');
    Route::get('/goals/scorecard', fn () => redirect()->route('goals.index', ['tab' => 'scorecard']))->middleware('permission:manage goals')->name('goals.scorecard');
    Route::get('/goals/settings', fn () => redirect()->route('goals.index', ['tab' => 'settings']))->middleware('permission:manage goals')->name('goals.settings');
    Route::get('/goals/team', fn () => redirect()->route('goals.index', ['tab' => 'team']))->middleware('permission:view team goals')->name('goals.team');
    Route::get('/goals/coaching', fn () => redirect()->route('goals.index', ['tab' => 'coaching']))->middleware('permission:coach goals')->name('goals.coaching');
    Route::get('/goals/reports', fn () => redirect()->route('goals.index', ['tab' => 'reports']))->middleware('permission:manage goals')->name('goals.reports');
    Route::get('/goals/reports/download', [GoalReportController::class, 'download'])->middleware('permission:manage goals')->name('goals.reports.download');
    Route::post('/goals/reports/email', [GoalReportController::class, 'email'])->middleware('permission:manage goals')->name('goals.reports.email');
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::get('/assessments/{assessment}/take', [AssessmentController::class, 'take'])->name('assessments.take');
    Route::get('/assessments/{assessment}/attempts/{attempt}', [AssessmentController::class, 'result'])->name('assessments.attempts.show');
    Route::view('/rank-advancement', 'rank-advancement.index')->name('rank-advancement.index');
    Route::view('/team/production', 'team.production.index')->name('team.production');
    Route::get('/team', [DownlineController::class, 'index'])->middleware('permission:view own team')->name('team.index');
    Route::get('/team/tree', [DownlineController::class, 'tree'])->middleware('permission:view team tree')->name('team.tree');
    Route::get('/team/tree/branches/{user}', [DownlineController::class, 'treeBranch'])->middleware('permission:view team tree')->name('team.tree.branch');
    Route::get('/team/tree/search', [DownlineController::class, 'treeSearch'])->middleware('permission:view team tree')->name('team.tree.search');
    Route::get('/team/org-chart', [DownlineController::class, 'orgChart'])->middleware('permission:view org chart')->name('team.org-chart');
    Route::get('/team/downlines', [DownlineController::class, 'table'])->middleware('permission:view team table')->name('team.downlines');
    Route::permanentRedirect('/team/table', '/team/downlines');
    Route::get('/team/hierarchy', [DownlineController::class, 'hierarchyTable'])->middleware('permission:view team tree')->name('team.hierarchy');
    Route::get('/team/member/{user}', [DownlineController::class, 'member'])->middleware('permission:view own team')->name('team.member');
    Route::post('/team/member/{user}/checklist-types/{typeCode}/start', [ChecklistTypeStartController::class, 'store'])->middleware('permission:view own team')->name('team.member.checklist-type.start');
    Route::get('/team/member/{user}/profile', [ProfileController::class, 'showMember'])->middleware('permission:view own team')->name('team.member.profile');
    Route::post('/team/member/{user}/production', [ProfileController::class, 'storeMemberProduction'])->middleware('permission:view own team')->name('team.member.production.store');
    Route::get('/team/member/{user}/tree', [DownlineController::class, 'tree'])->middleware('permission:view team tree')->name('team.member.tree');
    Route::get('/team/member/{user}/hierarchy', [DownlineController::class, 'hierarchyTable'])->middleware('permission:view team tree')->name('team.member.hierarchy');
    Route::get('/team/member/{user}/org-chart', [DownlineController::class, 'orgChart'])->middleware('permission:view org chart')->name('team.member.org-chart');
    Route::get('/team/export', [DownlineController::class, 'export'])->middleware('permission:export team data')->name('team.export');
    Route::get('/team/directs', [DownlineController::class, 'directs'])->middleware('permission:view own team')->name('team.directs');
    Route::get('/team/trainees', MyTrainees::class)->middleware('cfm.portal')->name('team.trainees');
    Route::get('/team/cfms', [CfmManagementController::class, 'index'])->middleware('cfm.management')->name('team.cfms');
    Route::patch('/team/cfms/{user}/licensed-jurisdictions', [CfmManagementController::class, 'updateLicensedJurisdictions'])->middleware('cfm.management')->name('team.cfms.licensed-jurisdictions.update');
    Route::post('/team/cfms/assign', [CfmManagementController::class, 'assign'])->middleware('cfm.management')->name('team.cfms.assign');
    Route::post('/team/downlines/assign-cfm', [CfmManagementController::class, 'bulkAssign'])->middleware('cfm.management')->name('team.downlines.assign-cfm');
    Route::post('/team/cfms', [CfmManagementController::class, 'store'])->middleware('cfm.management')->name('team.cfms.store');
    Route::view('/team/recruiting', 'team.recruiting.index')->middleware('permission:manage prospects')->name('team.recruiting.index');
    Route::get('/team/prospects', [ProspectManagementController::class, 'index'])->middleware('permission:manage prospects')->name('team.prospects');
    Route::get('/team/prospects/create', [ProspectManagementController::class, 'create'])->middleware('permission:manage prospects')->name('team.prospects.create');
    Route::get('/team/prospects/records/{prospect}', [ProspectManagementController::class, 'show'])->middleware('permission:manage prospects')->name('team.prospects.records.show');
    Route::get('/team/prospects/records/{prospect}/activity', [ProspectManagementController::class, 'activity'])->middleware('permission:manage prospects')->name('team.prospects.records.activity');
    Route::get('/team/prospects/records/{prospect}/edit', [ProspectManagementController::class, 'edit'])->middleware('permission:manage prospects')->name('team.prospects.records.edit');
    Route::patch('/team/prospects/records/{prospect}', [ProspectManagementController::class, 'update'])->middleware('permission:manage prospects')->name('team.prospects.records.update');
    Route::patch('/team/prospects/records/{prospect}/archive', [ProspectManagementController::class, 'archive'])->middleware('permission:manage prospects')->name('team.prospects.records.archive');
    Route::patch('/team/prospects/records/{prospect}/restore', [ProspectManagementController::class, 'restore'])->middleware('permission:manage prospects')->name('team.prospects.records.restore');
    Route::delete('/team/prospects/records/{prospect}', [ProspectManagementController::class, 'destroy'])->middleware('permission:manage prospects')->name('team.prospects.records.destroy');
    Route::get('/team/prospects/records/{prospect}/activities', [ProspectActivityController::class, 'index'])->middleware('permission:manage prospects')->name('team.prospects.activities.index');
    Route::post('/team/prospects/records/{prospect}/activities', [ProspectActivityController::class, 'store'])->middleware('permission:manage prospects')->name('team.prospects.activities.store');
    Route::patch('/team/prospects/records/{prospect}/activities/{activity}', [ProspectActivityController::class, 'update'])->middleware('permission:manage prospects')->name('team.prospects.activities.update');
    Route::delete('/team/prospects/records/{prospect}/activities/{activity}', [ProspectActivityController::class, 'destroy'])->middleware('permission:manage prospects')->name('team.prospects.activities.destroy');
    Route::get('/team/prospects/pipeline', [ProspectManagementController::class, 'pipeline'])->middleware('permission:manage prospects')->name('team.prospects.pipeline');
    Route::get('/team/prospects/pipeline/stages/{stage}/prospects', [ProspectManagementController::class, 'pipelineStageProspects'])->middleware('permission:manage prospects')->whereNumber('stage')->name('team.prospects.pipeline-stage-prospects');
    Route::get('/team/prospects/follow-ups', [ProspectManagementController::class, 'followUps'])->middleware('permission:manage prospects')->name('team.prospects.follow-ups');
    Route::get('/team/prospects/appointments', [ProspectManagementController::class, 'appointments'])->middleware('permission:manage prospects')->name('team.prospects.appointments');
    Route::get('/team/prospects/access-manager', [ProspectManagementController::class, 'accessManager'])->middleware('permission:manage prospects')->name('team.prospects.access-manager');
    Route::get('/team/prospects/shared-with-me', [ProspectManagementController::class, 'sharedWithMe'])->middleware('permission:manage prospects')->name('team.prospects.shared-with-me');
    Route::get('/team/prospects/shared-by-me', [ProspectManagementController::class, 'sharedByMe'])->middleware('permission:manage prospects')->name('team.prospects.shared-by-me');
    Route::get('/team/prospects/analytics', [ProspectManagementController::class, 'analytics'])->middleware('permission:manage prospects')->name('team.prospects.analytics');
    Route::get('/team/prospects/ai-coach', [ProspectManagementController::class, 'aiCoach'])->middleware('permission:manage prospects')->name('team.prospects.ai-coach');
    Route::get('/team/prospects/import', [ProspectManagementController::class, 'import'])->middleware('permission:import prospects')->name('team.prospects.import');
    Route::get('/team/prospects/import/template', [ProspectManagementController::class, 'downloadImportTemplate'])->middleware('permission:import prospects')->name('team.prospects.import-template');
    Route::get('/team/prospects/export', [ProspectManagementController::class, 'export'])->middleware('permission:export prospects')->name('team.prospects.export');
    Route::get('/team/prospects/{screen}', [ProspectManagementController::class, 'placeholder'])->middleware('permission:manage prospects')->name('team.prospects.screen');

    Route::prefix('team/fna')->name('team.fna.')->group(function (): void {
        Route::middleware('permission:manage fna records')->group(function (): void {
            Route::get('/', [FnaManagementController::class, 'dashboard'])->name('dashboard');
            Route::get('/records', [FnaManagementController::class, 'index'])->name('index');
            Route::get('/records/create', [FnaManagementController::class, 'create'])->name('create');
            Route::post('/records', [FnaManagementController::class, 'store'])->name('store');
            Route::get('/records/{fnaRecord}', [FnaManagementController::class, 'show'])->name('show');
            Route::get('/records/{fnaRecord}/edit', [FnaManagementController::class, 'edit'])->name('edit');
            Route::get('/records/{fnaRecord}/wizard', [FnaManagementController::class, 'wizard'])->name('wizard');
            Route::get('/dime-calculator', [FnaManagementController::class, 'dimeCalculator'])->name('dime');
            Route::get('/records/{fnaRecord}/export', [FnaManagementController::class, 'export'])
                ->middleware('permission:export fna records')
                ->name('export');
            Route::get('/records/{fnaRecord}/export/download', [FnaManagementController::class, 'exportDownload'])
                ->middleware('permission:export fna records')
                ->name('export.download');
        });

        Route::get('/cfm/review-queue', [FnaManagementController::class, 'cfmReviewQueue'])
            ->middleware('permission:review trainee fna records')
            ->name('cfm.review-queue');

        Route::get('/reports/agency', [FnaManagementController::class, 'agencyReports'])
            ->middleware('permission:view fna agency reports')
            ->name('reports.agency');
    });

    Route::get('/team/prospects/records/{prospect}/fna', [FnaManagementController::class, 'prospectFnas'])
        ->middleware('permission:manage prospects')
        ->name('team.prospects.records.fna');

    Route::get('/resources', [ResourceHubController::class, 'index'])->name('resources.index');
    Route::get('/resources/welcome-packet.pdf', [ResourceDocumentsController::class, 'welcomePacketPdf'])->name('resources.welcome-packet');
    Route::get('/resources/fap-overview.pdf', [ResourceDocumentsController::class, 'fapOverviewPdf'])->name('resources.fap-overview');
    Route::get('/resources/code-of-conduct.pdf', [ResourceDocumentsController::class, 'codeOfConductPdf'])->name('resources.code-of-conduct');
    Route::get('/resources/welcome-orientation.pdf', [ResourceDocumentsController::class, 'welcomeOrientationPdf'])->name('resources.welcome-orientation');
    Route::get('/resources/application.pdf', [ResourceDocumentsController::class, 'applicationGuidePdf'])->name('resources.application-guide');
    Route::get('/resources/goals-and-performance.pdf', [ResourceDocumentsController::class, 'goalsGuidePdf'])->name('resources.goals-guide');
    Route::get('/resources/prospect-sales-funnel.pdf', [ResourceDocumentsController::class, 'prospectSalesFunnelGuidePdf'])->name('resources.prospect-sales-funnel-guide');
    Route::get('/resources/fna-management.pdf', [ResourceDocumentsController::class, 'fnaManagementGuidePdf'])->name('resources.fna-management-guide');
    Route::get('/resources/training-academy.pdf', [ResourceDocumentsController::class, 'trainingAcademyGuidePdf'])->name('resources.training-academy-guide');
    Route::get('/resources/documents', [ResourceDocumentsController::class, 'index'])->name('resources.documents');
    Route::get('/resources/documents/{portalResource}/preview', [ResourceDocumentsController::class, 'preview'])->name('resources.documents.preview');
    Route::get('/resources/documents/{portalResource}/view', [ResourceDocumentsController::class, 'view'])->name('resources.documents.view');
    Route::get('/resources/documents/{portalResource}/download', [ResourceDocumentsController::class, 'download'])->name('resources.documents.download');
    Route::post('/resources/documents/{portalResource}/favorite', [ResourceDocumentsController::class, 'toggleFavorite'])->name('resources.documents.favorite');
    Route::post('/resources/documents/update-seeder', [ResourceDocumentsController::class, 'updateSeeder'])->name('resources.documents.update-seeder');
    Route::get('/resources/forms/associate-participation-agreement', [AssociateParticipationAgreementController::class, 'show'])->name('resources.forms.associate-participation-agreement');
    Route::post('/resources/forms/associate-participation-agreement', [AssociateParticipationAgreementController::class, 'store'])->name('resources.forms.associate-participation-agreement.store');
    Route::get('/resources/forms/associate-participation-agreement/download', [AssociateParticipationAgreementController::class, 'downloadPdf'])->name('resources.forms.associate-participation-agreement.download');
    Route::get('/resources/videos', [ResourceVideosController::class, 'index'])->name('resources.videos');
    Route::get('/resources/videos/{portalResource}/preview', [ResourceVideosController::class, 'preview'])->name('resources.videos.preview');
    Route::post('/resources/videos/{portalResource}/favorite', [ResourceVideosController::class, 'toggleFavorite'])->name('resources.videos.favorite');
    Route::view('/resources/recorded-webinars', 'resources.recorded-webinars')->name('resources.recorded-webinars');
    Route::redirect('/resources/zoom-links', '/resources/links');
    Route::get('/resources/links', [ResourceLinksController::class, 'index'])->name('resources.links');
    Route::get('/events', [CalendarController::class, 'agenda'])->middleware('permission:view calendar')->name('events.index');
    Route::get('/calendar', [CalendarController::class, 'index'])->middleware('permission:view calendar')->name('calendar.index');
    Route::post('/calendar', [CalendarController::class, 'store'])->middleware('permission:create calendar events')->name('calendar.store');
    Route::get('/calendar/month', [CalendarController::class, 'month'])->middleware('permission:view calendar')->name('calendar.month');
    Route::get('/calendar/week', [CalendarController::class, 'week'])->middleware('permission:view calendar')->name('calendar.week');
    Route::get('/calendar/day', [CalendarController::class, 'day'])->middleware('permission:view calendar')->name('calendar.day');
    Route::get('/calendar/agenda', [CalendarController::class, 'agenda'])->middleware('permission:view calendar')->name('calendar.agenda');
    Route::get('/calendar/events/{event}', [CalendarController::class, 'show'])->middleware('permission:view calendar')->name('calendar.events.show');
    Route::post('/calendar/categories', [CalendarController::class, 'storeCategory'])->middleware('permission:view calendar')->name('calendar.categories.store');
    Route::patch('/calendar/categories/{category}', [CalendarController::class, 'updateCategory'])->middleware('permission:view calendar')->name('calendar.categories.update');
    Route::delete('/calendar/categories/{category}', [CalendarController::class, 'destroyCategory'])->middleware('permission:view calendar')->name('calendar.categories.destroy');
    Route::get('/calendar/settings', [CalendarController::class, 'settings'])->middleware('permission:view calendar')->name('calendar.settings');
    Route::post('/calendar/schedule-blocks', [CalendarController::class, 'storeScheduleBlock'])->middleware('permission:view calendar')->name('calendar.schedule-blocks.store');
    Route::patch('/calendar/schedule-blocks/{block}', [CalendarController::class, 'updateScheduleBlock'])->middleware('permission:view calendar')->name('calendar.schedule-blocks.update');
    Route::delete('/calendar/schedule-blocks/{block}', [CalendarController::class, 'destroyScheduleBlock'])->middleware('permission:view calendar')->name('calendar.schedule-blocks.destroy');
    Route::post('/calendar/schedule-block-overrides', [CalendarController::class, 'storeScheduleBlockOverride'])->middleware('permission:view calendar')->name('calendar.schedule-block-overrides.store');
    Route::patch('/calendar/schedule-block-overrides/{override}', [CalendarController::class, 'updateScheduleBlockOverride'])->middleware('permission:view calendar')->name('calendar.schedule-block-overrides.update');
    Route::delete('/calendar/schedule-block-overrides/{override}', [CalendarController::class, 'destroyScheduleBlockOverride'])->middleware('permission:view calendar')->name('calendar.schedule-block-overrides.destroy');
    Route::get('/calendar/export', [CalendarController::class, 'export'])->middleware('permission:view calendar')->name('calendar.export');
    Route::get('/bookings/my-appointments', BookingsHub::class)->middleware('permission:view own bookings')->name('bookings.my');
    Route::redirect('/bookings/my', '/bookings/my-appointments');
    Route::get('/bookings', fn () => redirect()->route('bookings.my', ['tab' => 'dashboard']))->middleware('permission:view booking dashboard')->name('bookings.dashboard');
    Route::get('/bookings/availability', fn () => redirect()->route('bookings.my', ['tab' => 'availability']))->middleware('permission:manage own availability')->name('bookings.availability');
    Route::get('/bookings/event-types', fn () => redirect()->route('bookings.my', ['tab' => 'event-types']))->middleware('permission:manage own booking event types')->name('bookings.event-types');
    Route::get('/bookings/links', fn () => redirect()->route('bookings.my', ['tab' => 'links']))->middleware('permission:create booking links')->name('bookings.links');
    Route::get('/bookings/requests', fn () => redirect()->route('bookings.my', ['tab' => 'requests']))->middleware('permission:approve booking requests')->name('bookings.requests');
    Route::get('/bookings/calendar', fn () => redirect()->route('bookings.my', ['tab' => 'calendar']))->middleware('permission:view own bookings')->name('bookings.calendar');
    Route::get('/bookings/settings', fn () => redirect()->route('bookings.my', ['tab' => 'settings']))->middleware('permission:manage booking settings')->name('bookings.settings');
    Route::redirect('/recognition', '/communications/recognition')->name('recognition.index');
    Route::redirect('/announcements', '/communications')->name('announcements.index');

    Route::middleware('permission:view announcements|manage announcements')->prefix('communications')->name('communications.')->group(function (): void {
        Route::get('/', CommunicationHub::class)->name('index');
        Route::get('/bookmarks', AnnouncementBookmarks::class)->name('bookmarks');
        Route::get('/archive', AnnouncementArchive::class)->name('archive');
        Route::get('/recognition', RecognitionCenter::class)->name('recognition');
        Route::get('/recognition/create', RecognitionComposer::class)
            ->middleware('permission:manage recognition posts')
            ->name('recognition.create');
        Route::get('/leadership', LeadershipDesk::class)->name('leadership');
        Route::get('/campaigns', CampaignCenter::class)->name('campaigns.index');
        Route::get('/campaigns/create', CampaignComposer::class)
            ->middleware('permission:manage campaigns')
            ->name('campaigns.create');
        Route::get('/campaigns/{campaign:slug}', CampaignShow::class)->name('campaigns.show');
        Route::get('/events/create', EventAnnouncementComposer::class)
            ->middleware('permission:create announcements|manage announcements')
            ->name('events.create');
        Route::get('/create', AnnouncementComposer::class)
            ->middleware('permission:create announcements|manage announcements')
            ->name('create');
        Route::get('/{announcement:slug}', AnnouncementShow::class)->name('show');
    });
    Route::get('/notifications', NotificationCenter::class)->middleware('permission:view notifications')->name('notifications.index');
    Route::get('/notifications/preferences', NotificationPreferences::class)->middleware('permission:manage own notification preferences')->name('notifications.preferences');
    Route::get('/notifications/push/vapid-public-key', [NotificationDeviceTokenController::class, 'vapidPublicKey'])->middleware('permission:manage own notification preferences')->name('notifications.push.vapid-public-key');
    Route::post('/notifications/device-tokens', [NotificationDeviceTokenController::class, 'store'])->middleware('permission:manage own notification preferences')->name('notifications.device-tokens.store');
    Route::delete('/notifications/device-tokens', [NotificationDeviceTokenController::class, 'destroy'])->middleware('permission:manage own notification preferences')->name('notifications.device-tokens.destroy');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->middleware('permission:view notifications')->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->middleware('permission:view notifications')->name('notifications.mark-read');

    Route::prefix('support')->name('support.')->group(function (): void {
        Route::get('/', [SupportController::class, 'index'])
            ->middleware('permission:submit support ticket')
            ->name('index');
        Route::get('/documentation/{guide}', [SupportController::class, 'documentation'])
            ->middleware('permission:submit support ticket')
            ->name('documentation');
        Route::get('/documentation/{guide}/pdf', [SupportController::class, 'documentationPdf'])
            ->middleware('permission:submit support ticket')
            ->name('documentation.pdf');
        Route::get('/documentation/{guide}/pdf/view', [SupportController::class, 'documentationPdfInline'])
            ->middleware('permission:submit support ticket')
            ->name('documentation.pdf.view');
        Route::get('/tickets/{ticket}', function (SupportTicket $ticket) {
            abort_unless(auth()->user()?->can('view', $ticket), 403);

            return view('support.show', ['ticket' => $ticket]);
        })
            ->middleware('permission:view own support tickets')
            ->name('show');
    });

    Route::prefix('admin/support')
        ->name('admin.support.')
        ->middleware('permission:view all support tickets|view support reports')
        ->group(function (): void {
            Route::view('/', 'admin.support.index')->name('index');
            Route::view('/wishlist', 'admin.support.wishlist')
                ->middleware('permission:manage enhancement wishlist|manage support settings')
                ->name('wishlist');
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/completion-photo-preview', [ProfileController::class, 'completionPhotoPreview'])->name('profile.completion-photo-preview');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/production', [ProfileController::class, 'storeProduction'])->name('profile.production.store');
    Route::patch('/profile/licenses', [ProfileController::class, 'updateInsuranceLicenses'])->name('profile.licenses.update');
    Route::patch('/profile/invite-link', [ProfileController::class, 'updateInviteLink'])->name('profile.invite-link.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::post('/profile/invitations', [ProfileController::class, 'createInvitation'])->name('profile.invitations.store');
    Route::post('/profile/invitations/{invitation}/send', [ProfileController::class, 'sendInvitationEmail'])->name('profile.invitations.send');
    Route::delete('/profile/invitations/{invitation}', [ProfileController::class, 'destroyInvitation'])->name('profile.invitations.destroy');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super-admin|admin|agency-owner|team-leader|certified-field-mentor|trainer')
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('index');
            Route::get('/settings', AdminSettingsPage::class)->middleware('role:super-admin|admin|agency-owner')->name('settings');
            Route::get('/payments', SubscriptionPaymentsPage::class)->middleware('role:super-admin|admin|agency-owner')->name('payments');
            Route::get('/crm/pipelines', PipelineManager::class)
                ->middleware('permission:manage crm funnels')
                ->name('crm.pipelines');
            Route::middleware(['role:super-admin|admin|agency-owner', 'permission:manage users'])->group(function () {
                Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
                Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
                Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
                Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->withTrashed()->name('users.edit');
                Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
                Route::patch('/users/{user}/messaging/suspend', [UserManagementController::class, 'suspendMessaging'])->name('users.messaging.suspend');
                Route::patch('/users/{user}/messaging/restore', [UserManagementController::class, 'restoreMessaging'])->name('users.messaging.restore');
                Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
                Route::patch('/users/{user}/restore', [UserManagementController::class, 'restore'])->name('users.restore');
            });
            Route::middleware('role:super-admin|admin|agency-owner|team-leader|certified-field-mentor|trainer')->prefix('management')->name('management.')->group(function () {
                Route::get('/', [AdminManagementController::class, 'index'])->name('index');
                Route::get('/{resource}', [AdminManagementController::class, 'resourceIndex'])->name('resource.index');
                Route::get('/{resource}/create', [AdminManagementController::class, 'create'])->name('create');
                Route::post('/{resource}', [AdminManagementController::class, 'store'])->name('store');
                Route::post('/{resource}/update-seeder', [AdminManagementController::class, 'updateSeeder'])->name('update-seeder');
                Route::get('/{resource}/{record}', [AdminManagementController::class, 'show'])->name('show');
                Route::get('/{resource}/{record}/edit', [AdminManagementController::class, 'edit'])->name('edit');
                Route::patch('/{resource}/{record}', [AdminManagementController::class, 'update'])->name('update');
                Route::patch('/{resource}/{record}/reorder', [AdminManagementController::class, 'reorder'])->name('reorder');
                Route::patch('/{resource}/{record}/status', [AdminManagementController::class, 'toggleStatus'])->name('status');
                Route::delete('/{resource}/{record}', [AdminManagementController::class, 'destroy'])->name('destroy');
                Route::patch('/{resource}/{record}/restore', [AdminManagementController::class, 'restore'])->name('restore');
                Route::get('/resources/{record}/view-pdf', [AdminManagementController::class, 'viewResourcePdf'])->name('resources.view-pdf');
                Route::post('/resources/{record}/favorite', [AdminManagementController::class, 'toggleResourceFavorite'])->name('resources.favorite');
                Route::post('/resources/{record}/generate-pdf', [AdminManagementController::class, 'generateResourcePdf'])->name('resources.generate-pdf');
            });
            Route::get('/roles', RolesPermissionsManager::class)->middleware('permission:manage roles')->name('roles.index');
            Route::get('/ranks', RankAdministrationManager::class)
                ->middleware(['role:super-admin|admin|agency-owner', 'permission:manage ranks'])
                ->name('ranks.index');
            Route::view('/checklists', 'admin.checklists.index')->middleware('role:super-admin|admin|agency-owner|team-leader|certified-field-mentor|trainer')->name('checklists.index');
            Route::prefix('backups')
                ->name('backups.')
                ->middleware(['role:super-admin', 'permission:view backups'])
                ->group(function (): void {
                    Route::get('/', BackupDashboard::class)->name('index');
                    Route::get('/create', CreateBackup::class)->middleware('permission:create backups')->name('create');
                    Route::get('/history', BackupHistory::class)->name('history');
                    Route::get('/restore', RestoreBackup::class)->middleware('permission:restore backups')->name('restore');
                    Route::get('/restores', RestoreHistory::class)->name('restores');
                    Route::get('/{backup}/download', BackupDownloadController::class)->middleware('permission:download backups')->name('download');
                });
            Route::get('/training', [AdminTrainingController::class, 'index'])->middleware('permission:manage training')->name('training.index');
            Route::get('/training/management', [AdminTrainingController::class, 'management'])->middleware('permission:manage training enrollments')->name('training.management');
            Route::get('/training/courses', [AdminTrainingController::class, 'courses'])->middleware('permission:manage training')->name('training.courses.index');
            Route::get('/training/courses/{module}', [AdminTrainingController::class, 'course'])->middleware('permission:manage training')->name('training.courses.show');
            Route::get('/training/paths', [AdminTrainingController::class, 'paths'])->middleware('permission:manage training')->name('training.paths.index');
            Route::get('/training/paths/{path}', [AdminTrainingController::class, 'path'])->middleware('permission:manage training')->name('training.paths.show');
            Route::get('/cfm-certification', CertificationReviewManager::class)
                ->middleware('permission:manage CFM certification')
                ->name('cfm.index');
            Route::get('/cfm-performance', CfmPerformanceManager::class)
                ->middleware('permission:manage mentor reviews')
                ->name('cfm-performance.index');
            Route::prefix('notifications')
                ->name('notifications.')
                ->middleware('permission:view notification logs')
                ->group(function (): void {
                    Route::view('/', 'admin.notifications.index')->name('index');
                    Route::view('/delivery-logs', 'admin.notifications.delivery-logs')->name('delivery-logs');
                });
            Route::prefix('communications')
                ->name('communications.')
                ->group(function (): void {
                    Route::view('/', 'admin.communications.index')
                        ->middleware('permission:view communication analytics')
                        ->name('index');
                    Route::view('/acknowledgements', 'admin.communications.acknowledgements')
                        ->middleware('permission:view communication analytics')
                        ->name('acknowledgements');
                    Route::view('/broadcasts', 'admin.communications.broadcasts')
                        ->middleware('permission:send broadcast')
                        ->name('broadcasts');
                    Route::view('/newsletters', 'admin.communications.newsletters')
                        ->middleware('permission:manage newsletters')
                        ->name('newsletters');
                });
        });
});

require __DIR__.'/auth.php';
