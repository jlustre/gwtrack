<?php

namespace App\Livewire\Cfm;

use App\Models\CfmMeeting;
use App\Models\CfmTask;
use App\Models\Checklist;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CfmPortal\CfmCoachingAssistantService;
use App\Services\CfmPortal\CfmCoachingNoteService;
use App\Services\CfmPortal\CfmMeetingService;
use App\Services\CfmPortal\CfmNotificationService;
use App\Services\CfmPortal\CfmPortalDashboardService;
use App\Services\CfmPortal\CfmProgressReportService;
use App\Services\CfmPortal\CfmPromotionReadinessService;
use App\Services\CfmPortal\CfmRiskAssessmentService;
use App\Services\CfmPortal\CfmTaskService;
use App\Services\CfmPortal\CfmTraineeCenterService;
use App\Services\CfmPortal\CfmTraineeProfileService;
use App\Services\CfmPortalService;
use App\Services\CfmTraineeChecklistService;
use App\Services\ChecklistService;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('CFM Portal')]
class Portal extends Component
{
    #[Url(as: 'cfm', except: null)]
    public ?int $cfmUserId = null;

    #[Url(as: 'trainee', except: null)]
    public ?int $selectedTraineeId = null;

    #[Url(as: 'filter', except: 'all')]
    public string $traineeFilter = 'all';

    #[Url(as: 'section', except: 'overview')]
    public string $activeSection = 'overview';

    public string $traineeSearch = '';

    public bool $sidebarOpen = false;

    public string $reviewComments = '';

    public string $taskStatusFilter = 'open';

    public string $noteCategoryFilter = 'all';

    public string $taskTitle = '';

    public string $taskNotes = '';

    public string $taskCategory = 'coaching';

    public string $taskPriority = 'normal';

    public ?string $taskDueDate = null;

    public string $noteCategory = 'general';

    public string $noteBody = '';

    public ?int $editingNoteId = null;

    public string $meetingStatusFilter = 'upcoming';

    public string $meetingTitle = '';

    public string $meetingType = 'coaching';

    public ?string $meetingStartsAt = null;

    public ?string $meetingEndsAt = null;

    public ?int $selectedMeetingId = null;

    public string $meetingNoteSummary = '';

    public string $meetingActionItems = '';

    public string $reportType = 'progress_snapshot';

    public string $reportAudience = 'cfm';

    public bool $reportNotifyTrainee = false;

    public string $actionPlanTitle = '';

    public string $actionPlanSummary = '';

    public string $actionPlanSteps = '';

    public ?string $actionPlanTargetDate = null;

    public string $promotionStatus = 'tracking';

    public string $aiFocusArea = 'general';

    public string $aiQuestion = '';

    public string $aiAnswer = '';

    public string $smsTemplate = 'check_in';

    public string $smsCustomBody = '';

    public string $reportNotifyChannel = 'in_app';

    public ?string $traineeQuickActionModal = null;

    public string $quickMessageBody = '';

    public function selectTrainee(?int $traineeId): void
    {
        $this->selectedTraineeId = $traineeId;
        $this->activeSection = 'overview';
        $this->sidebarOpen = false;
        $this->traineeQuickActionModal = null;
        $this->resetPhaseForms();
    }

    public function setSection(string $section): void
    {
        $this->activeSection = $section;
        $this->reviewComments = '';

        if (! in_array($section, ['tasks', 'notes', 'meetings', 'reports', 'risk', 'promotion', 'assistant'], true)) {
            $this->resetPhaseForms();
        }
    }

    public function updatedCfmUserId(): void
    {
        $this->selectedTraineeId = null;
        $this->activeSection = 'overview';
        $this->resetPhaseForms();
    }

    public function updatedTaskStatusFilter(): void
    {
        //
    }

    public function updatedNoteCategoryFilter(): void
    {
        //
    }

    public function updatedMeetingStatusFilter(): void
    {
        //
    }

    public function reviewChecklistItem(int $progressId, string $decision): void
    {
        $this->validate([
            'reviewComments' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(in_array($decision, ['confirmed', 'rejected'], true), 422);
        abort_unless($this->selectedTraineeId, 422);

        app(ChecklistService::class)->reviewUserProgress(
            auth()->user(),
            $progressId,
            $decision,
            $this->reviewComments !== '' ? $this->reviewComments : null,
        );

        $this->reviewComments = '';

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => $decision === 'confirmed'
                ? 'Checklist item confirmed.'
                : 'Checklist item returned for revision.',
        ]);
    }

    public function toggleMentoringItem(int $checklistId, bool $completed): void
    {
        abort_unless($this->selectedTraineeId, 422);

        $assignment = $this->activeAssignment();

        if (! $assignment) {
            return;
        }

        app(CfmTraineeChecklistService::class)->updateProgress(
            $assignment,
            Checklist::query()->findOrFail($checklistId),
            auth()->user(),
            $completed,
        );

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => $completed
                ? 'Mentoring checklist item marked complete.'
                : 'Mentoring checklist item reopened.',
        ]);
    }

    public function saveMentoringItemNotes(int $checklistId, string $notes): void
    {
        if (strlen($notes) > 2000) {
            $this->addError('mentoring_notes', 'Notes may not exceed 2000 characters.');

            return;
        }

        abort_unless($this->selectedTraineeId, 422);

        $assignment = $this->activeAssignment();

        if (! $assignment) {
            return;
        }

        $service = app(CfmTraineeChecklistService::class);
        $payload = $service->checklistForAssignment($assignment);
        $isCompleted = collect($payload['phases'])
            ->flatMap(fn (array $phase) => collect($phase['sections'])->flatMap(fn (array $section) => $section['items']))
            ->firstWhere('id', $checklistId)['is_completed'] ?? false;

        $service->updateProgress(
            $assignment,
            Checklist::query()->findOrFail($checklistId),
            auth()->user(),
            (bool) $isCompleted,
            $notes !== '' ? $notes : null,
        );

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Mentoring notes saved.',
        ]);
    }

    public function openTraineeQuickActionModal(string $action): void
    {
        if (! in_array($action, ['message', 'meeting', 'task', 'profile'], true)) {
            return;
        }

        if (! $this->resolvedTrainee()) {
            return;
        }

        $this->traineeQuickActionModal = $action;

        if ($action === 'meeting' && $this->meetingTitle === '') {
            $trainee = $this->resolvedTrainee();
            $this->meetingTitle = 'Coaching check-in with '.$trainee->name;
        }
    }

    public function closeTraineeQuickActionModal(): void
    {
        $this->traineeQuickActionModal = null;
        $this->quickMessageBody = '';
    }

    public function sendQuickMessage(MessagingService $messaging): void
    {
        $this->validate([
            'quickMessageBody' => ['required', 'string', 'max:5000'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $conversation = $messaging->findOrCreateDirectConversation($cfm, $trainee);
        $messaging->sendMessage($cfm, $conversation, $this->quickMessageBody);

        $this->quickMessageBody = '';
        $this->traineeQuickActionModal = null;

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Message sent to '.$trainee->name.' via GWTrack messaging.',
        ]);
    }

    public function createTask(CfmTaskService $tasks): void
    {
        $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskNotes' => ['nullable', 'string', 'max:2000'],
            'taskCategory' => ['required', 'in:coaching,prospecting,training,licensing,fap,recruiting,admin'],
            'taskPriority' => ['required', 'in:low,normal,high,urgent'],
            'taskDueDate' => ['nullable', 'date'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $tasks->create($cfm, $trainee, auth()->user(), [
            'title' => $this->taskTitle,
            'notes' => $this->taskNotes,
            'category' => $this->taskCategory,
            'priority' => $this->taskPriority,
            'due_date' => $this->taskDueDate,
        ]);

        $this->reset(['taskTitle', 'taskNotes', 'taskDueDate']);
        $this->taskCategory = 'coaching';
        $this->taskPriority = 'normal';
        $this->traineeQuickActionModal = null;

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Task assigned to trainee.',
        ]);
    }

    public function updateTaskStatus(int $taskId, string $status, CfmTaskService $tasks): void
    {
        $cfm = $this->resolvedCfmUser();
        $task = $tasks->findForCfm($cfm, $taskId);
        $tasks->updateStatus($cfm, $task, auth()->user(), $status);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Task status updated.',
        ]);
    }

    public function deleteTask(int $taskId, CfmTaskService $tasks): void
    {
        $cfm = $this->resolvedCfmUser();
        $task = $tasks->findForCfm($cfm, $taskId);
        $tasks->delete($cfm, $task, auth()->user());

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Task deleted.',
        ]);
    }

    public function saveNote(CfmCoachingNoteService $notes): void
    {
        $this->validate([
            'noteCategory' => ['required', 'in:general,strength,weakness,opportunity,challenge,recommendation'],
            'noteBody' => ['required', 'string', 'max:5000'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        if ($this->editingNoteId) {
            $note = $notes->findForCfm($cfm, $this->editingNoteId);
            $notes->update($cfm, $note, auth()->user(), [
                'category' => $this->noteCategory,
                'body' => $this->noteBody,
            ]);
            $message = 'Coaching note updated.';
        } else {
            $notes->create($cfm, $trainee, auth()->user(), [
                'category' => $this->noteCategory,
                'body' => $this->noteBody,
            ]);
            $message = 'Coaching note saved.';
        }

        $this->resetNoteForm();

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function editNote(int $noteId, CfmCoachingNoteService $notes): void
    {
        $cfm = $this->resolvedCfmUser();
        $note = $notes->findForCfm($cfm, $noteId);

        $this->editingNoteId = $note->id;
        $this->noteCategory = $note->category;
        $this->noteBody = $note->body;
    }

    public function cancelNoteEdit(): void
    {
        $this->resetNoteForm();
    }

    public function deleteNote(int $noteId, CfmCoachingNoteService $notes): void
    {
        $cfm = $this->resolvedCfmUser();
        $note = $notes->findForCfm($cfm, $noteId);
        $notes->delete($cfm, $note);

        if ($this->editingNoteId === $noteId) {
            $this->resetNoteForm();
        }

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Coaching note deleted.',
        ]);
    }

    public function createMeeting(CfmMeetingService $meetings): void
    {
        $this->validate([
            'meetingTitle' => ['required', 'string', 'max:255'],
            'meetingType' => ['required', 'in:'.implode(',', \App\Models\CfmMeeting::TYPES)],
            'meetingStartsAt' => ['required', 'date'],
            'meetingEndsAt' => ['nullable', 'date', 'after:meetingStartsAt'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $meetings->create($cfm, $trainee, auth()->user(), [
            'title' => $this->meetingTitle,
            'type' => $this->meetingType,
            'starts_at' => $this->meetingStartsAt,
            'ends_at' => $this->meetingEndsAt,
        ]);

        $this->reset(['meetingTitle', 'meetingStartsAt', 'meetingEndsAt']);
        $this->meetingType = 'coaching';
        $this->traineeQuickActionModal = null;

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Meeting scheduled.',
        ]);
    }

    public function importBookingMeeting(int $bookingId, CfmMeetingService $meetings): void
    {
        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $booking = $meetings->findBookingForCfm($cfm, $bookingId);
        $meetings->importFromBooking($cfm, $trainee, auth()->user(), $booking);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Calendar booking linked as a meeting.',
        ]);
    }

    public function updateMeetingStatus(int $meetingId, string $status, CfmMeetingService $meetings): void
    {
        $cfm = $this->resolvedCfmUser();
        $meeting = $meetings->findForCfm($cfm, $meetingId);
        $meetings->updateStatus($cfm, $meeting, auth()->user(), $status);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Meeting status updated.',
        ]);
    }

    public function selectMeetingForNotes(int $meetingId, CfmMeetingService $meetings): void
    {
        $cfm = $this->resolvedCfmUser();
        $meeting = $meetings->findForCfm($cfm, $meetingId);

        $this->selectedMeetingId = $meeting->id;
        $this->meetingNoteSummary = $meeting->latestNote?->summary ?? '';
        $this->meetingActionItems = collect($meeting->latestNote?->action_items ?? [])
            ->implode("\n");
    }

    public function cancelMeetingNotes(): void
    {
        $this->resetMeetingNoteForm();
    }

    public function saveMeetingNotes(CfmMeetingService $meetings): void
    {
        $this->validate([
            'selectedMeetingId' => ['required', 'integer'],
            'meetingNoteSummary' => ['nullable', 'string', 'max:5000'],
            'meetingActionItems' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_unless($this->selectedMeetingId, 422);

        $cfm = $this->resolvedCfmUser();
        $meeting = $meetings->findForCfm($cfm, $this->selectedMeetingId);

        $meetings->saveNotes($cfm, $meeting, auth()->user(), [
            'summary' => $this->meetingNoteSummary,
            'action_items' => $this->meetingActionItems,
        ]);

        $this->resetMeetingNoteForm();

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Meeting notes saved.',
        ]);
    }

    public function deleteMeeting(int $meetingId, CfmMeetingService $meetings): void
    {
        $cfm = $this->resolvedCfmUser();
        $meeting = $meetings->findForCfm($cfm, $meetingId);
        $meetings->delete($cfm, $meeting);

        if ($this->selectedMeetingId === $meetingId) {
            $this->resetMeetingNoteForm();
        }

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Meeting removed.',
        ]);
    }

    public function generateReport(
        CfmProgressReportService $reports,
        CfmNotificationService $notifications,
    ): void {
        $this->validate([
            'reportType' => ['required', 'in:'.implode(',', \App\Models\CfmProgressReport::TYPES)],
            'reportAudience' => ['required', 'in:'.implode(',', \App\Models\CfmProgressReport::AUDIENCES)],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $report = $reports->generate($cfm, $trainee, auth()->user(), $this->reportType, $this->reportAudience);

        if ($this->reportNotifyTrainee) {
            $notifications->notifyProgressReport($cfm, $trainee, auth()->user(), $report, $this->reportNotifyChannel);
        }

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => $this->reportNotifyTrainee
                ? 'Report generated and trainee notified.'
                : 'Progress report generated.',
        ]);
    }

    public function runRiskAssessment(CfmRiskAssessmentService $risk): void
    {
        $cfm = $this->resolvedCfmUser();

        abort_unless($this->selectedTraineeId, 422);

        $risk->centerFor($cfm, $this->selectedTraineeId, refresh: true);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Risk assessment refreshed.',
        ]);
    }

    public function createActionPlan(CfmRiskAssessmentService $risk): void
    {
        $this->validate([
            'actionPlanTitle' => ['required', 'string', 'max:255'],
            'actionPlanSummary' => ['nullable', 'string', 'max:2000'],
            'actionPlanSteps' => ['nullable', 'string', 'max:5000'],
            'actionPlanTargetDate' => ['nullable', 'date'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $risk->createActionPlan($cfm, $trainee, auth()->user(), [
            'title' => $this->actionPlanTitle,
            'summary' => $this->actionPlanSummary,
            'steps' => $this->actionPlanSteps,
            'target_date' => $this->actionPlanTargetDate,
        ]);

        $this->reset(['actionPlanTitle', 'actionPlanSummary', 'actionPlanSteps', 'actionPlanTargetDate']);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Action plan created.',
        ]);
    }

    public function completeActionPlan(int $planId, CfmRiskAssessmentService $risk): void
    {
        $cfm = $this->resolvedCfmUser();
        $plan = $risk->findActionPlanForCfm($cfm, $planId);
        $risk->updateActionPlanStatus($cfm, $plan, 'completed');

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Action plan marked complete.',
        ]);
    }

    public function refreshPromotionReadiness(CfmPromotionReadinessService $promotion): void
    {
        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $promotion->syncForTrainee($cfm, $trainee);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Promotion readiness updated.',
        ]);
    }

    public function updatePromotionStatus(CfmPromotionReadinessService $promotion): void
    {
        $this->validate([
            'promotionStatus' => ['required', 'in:'.implode(',', \App\Models\CfmPromotion::STATUSES)],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $record = $promotion->findForCfm($cfm, $trainee->id);
        $promotion->updateStatus($cfm, $record, $this->promotionStatus);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'Promotion status updated.',
        ]);
    }

    public function generateCoachingBrief(CfmCoachingAssistantService $assistant): void
    {
        $this->validate([
            'aiFocusArea' => ['required', 'in:'.implode(',', \App\Models\CfmCoachingSession::FOCUS_AREAS)],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $assistant->generateBrief($cfm, $trainee, auth()->user(), $this->aiFocusArea);

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'AI coaching brief generated.',
        ]);
    }

    public function askAssistant(CfmCoachingAssistantService $assistant): void
    {
        $this->validate([
            'aiQuestion' => ['required', 'string', 'max:500'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $this->aiAnswer = $assistant->answerQuestion($cfm, $trainee, $this->aiQuestion);
    }

    public function useAssistantPrompt(string $prompt): void
    {
        $this->aiQuestion = $prompt;
        $this->askAssistant(app(CfmCoachingAssistantService::class));
    }

    public function sendSmsToTrainee(CfmNotificationService $notifications): void
    {
        $this->validate([
            'smsTemplate' => ['required', 'string'],
            'smsCustomBody' => ['nullable', 'string', 'max:320'],
        ]);

        $cfm = $this->resolvedCfmUser();
        $trainee = $this->resolvedTrainee();

        if (! $trainee) {
            return;
        }

        $notifications->notifyFromSmsTemplate(
            $cfm,
            $trainee,
            auth()->user(),
            $this->smsTemplate,
            $this->smsCustomBody !== '' ? $this->smsCustomBody : null,
        );

        $this->smsCustomBody = '';

        session()->flash('profile_feedback', [
            'type' => 'success',
            'message' => 'SMS notification sent to trainee.',
        ]);
    }

    public function render(
        CfmPortalService $cfmPortal,
        CfmPortalDashboardService $dashboard,
        CfmTraineeProfileService $traineeProfiles,
        CfmTraineeCenterService $centers,
        CfmTaskService $tasks,
        CfmCoachingNoteService $coachingNotes,
        CfmMeetingService $meetings,
        CfmProgressReportService $progressReports,
        CfmNotificationService $cfmNotifications,
        CfmRiskAssessmentService $riskAssessment,
        CfmPromotionReadinessService $promotionReadiness,
        CfmCoachingAssistantService $coachingAssistant,
    ): View {
        $viewer = auth()->user()->loadMissing(['profile', 'rank', 'team']);
        $portal = $cfmPortal->payloadFor($viewer, $this->cfmUserId);
        $cfmUser = $portal['cfmUser'];

        $trainees = $dashboard->traineesFor($cfmUser);
        $summary = $dashboard->summaryFor($cfmUser);
        $pendingAssignments = $dashboard->pendingAssignmentsFor($cfmUser);
        $aiSuggestions = $dashboard->aiSuggestionsFor($cfmUser, $trainees);
        $aiPriorities = $coachingAssistant->rosterPrioritiesFor($cfmUser, $trainees);

        $filteredTrainees = $this->filteredTrainees($trainees);

        if ($this->selectedTraineeId && ! $trainees->contains('id', $this->selectedTraineeId)) {
            $this->selectedTraineeId = null;
        }

        $trainee360 = $this->selectedTraineeId
            ? $traineeProfiles->profile360($cfmUser, $this->selectedTraineeId)
            : null;

        $sectionCenter = null;

        if ($this->selectedTraineeId && $this->isCenterSection($this->activeSection)) {
            $sectionCenter = match ($this->activeSection) {
                'tasks' => $tasks->centerFor($cfmUser, $this->selectedTraineeId, $this->taskStatusFilter),
                'notes' => $coachingNotes->centerFor($cfmUser, $this->selectedTraineeId, $this->noteCategoryFilter),
                'meetings' => $meetings->centerFor($cfmUser, $this->selectedTraineeId, $this->meetingStatusFilter),
                'reports' => array_merge(
                    $progressReports->centerFor($cfmUser, $this->selectedTraineeId) ?? [],
                    ['notifications' => $cfmNotifications->recentForTrainee($cfmUser, $this->selectedTraineeId) ?? []],
                ),
                'risk' => $riskAssessment->centerFor($cfmUser, $this->selectedTraineeId),
                'promotion' => $promotionReadiness->centerFor($cfmUser, $this->selectedTraineeId),
                'assistant' => $coachingAssistant->centerFor($cfmUser, $this->selectedTraineeId),
                default => $centers->centerFor($cfmUser, $this->selectedTraineeId, $this->activeSection),
            };
        }

        return view('livewire.cfm.portal', [
            'viewer' => $viewer,
            'portal' => $portal,
            'cfmUser' => $cfmUser,
            'summary' => $summary,
            'trainees' => $filteredTrainees,
            'trainee360' => $trainee360,
            'sectionCenter' => $sectionCenter,
            'pendingAssignments' => $pendingAssignments,
            'aiSuggestions' => $aiSuggestions,
            'aiPriorities' => $aiPriorities,
            'todayLabel' => now()->format('l, F j, Y'),
            'sections' => $this->sections(),
            'rosterExportUrl' => route('cfm.portal.roster.export'),
            'taskCategories' => CfmTask::CATEGORIES,
            'taskPriorities' => CfmTask::PRIORITIES,
            'meetingTypes' => CfmMeeting::TYPES,
        ])->layout('layouts.app');
    }

    private function resolvedCfmUser(): User
    {
        return app(CfmPortalService::class)
            ->payloadFor(auth()->user(), $this->cfmUserId)['cfmUser'];
    }

    private function resolvedTrainee(): ?User
    {
        if (! $this->selectedTraineeId) {
            return null;
        }

        return app(CfmTraineeCenterService::class)
            ->resolveTrainee($this->resolvedCfmUser(), $this->selectedTraineeId);
    }

    private function activeAssignment(): ?MentorAssignment
    {
        if (! $this->selectedTraineeId) {
            return null;
        }

        return MentorAssignment::query()
            ->where('mentor_id', $this->resolvedCfmUser()->id)
            ->where('apprentice_id', $this->selectedTraineeId)
            ->where('status', 'active')
            ->latest('id')
            ->first();
    }

    private function isCenterSection(string $section): bool
    {
        return in_array($section, [
            'onboarding', 'fap', 'licensing', 'training', 'goals', 'tasks', 'notes', 'meetings', 'reports', 'risk', 'promotion', 'assistant',
        ], true);
    }

    private function resetPhaseForms(): void
    {
        $this->reviewComments = '';
        $this->taskStatusFilter = 'open';
        $this->noteCategoryFilter = 'all';
        $this->meetingStatusFilter = 'upcoming';
        $this->reportType = 'progress_snapshot';
        $this->reportAudience = 'cfm';
        $this->reportNotifyTrainee = false;
        $this->reportNotifyChannel = 'in_app';
        $this->promotionStatus = 'tracking';
        $this->aiFocusArea = 'general';
        $this->aiQuestion = '';
        $this->aiAnswer = '';
        $this->smsTemplate = 'check_in';
        $this->smsCustomBody = '';
        $this->traineeQuickActionModal = null;
        $this->quickMessageBody = '';
        $this->reset(['taskTitle', 'taskNotes', 'taskDueDate', 'meetingTitle', 'meetingStartsAt', 'meetingEndsAt', 'actionPlanTitle', 'actionPlanSummary', 'actionPlanSteps', 'actionPlanTargetDate']);
        $this->taskCategory = 'coaching';
        $this->taskPriority = 'normal';
        $this->meetingType = 'coaching';
        $this->resetNoteForm();
        $this->resetMeetingNoteForm();
    }

    private function resetNoteForm(): void
    {
        $this->editingNoteId = null;
        $this->noteCategory = 'general';
        $this->noteBody = '';
    }

    private function resetMeetingNoteForm(): void
    {
        $this->selectedMeetingId = null;
        $this->meetingNoteSummary = '';
        $this->meetingActionItems = '';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $trainees
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function filteredTrainees($trainees)
    {
        $needle = strtolower(trim($this->traineeSearch));

        return $trainees
            ->filter(function (array $trainee) use ($needle): bool {
                if ($needle !== '' && ! str_contains(strtolower($trainee['name']), $needle)) {
                    return false;
                }

                return match ($this->traineeFilter) {
                    'active' => $trainee['is_active'] && $trainee['status'] === 'active',
                    'new' => $trainee['is_new'],
                    'at_risk' => $trainee['is_at_risk'],
                    'licensing' => $trainee['status'] === 'licensing',
                    'fap' => $trainee['status'] === 'fap',
                    'promotion_ready' => $trainee['is_promotion_ready'],
                    'inactive' => ! $trainee['is_active'],
                    default => true,
                };
            })
            ->values();
    }

    /**
     * @return list<array{key: string, label: string, phase: int}>
     */
    private function sections(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'phase' => 1],
            ['key' => 'onboarding', 'label' => 'Onboarding', 'phase' => 2],
            ['key' => 'fap', 'label' => 'FAP', 'phase' => 2],
            ['key' => 'licensing', 'label' => 'Licensing', 'phase' => 2],
            ['key' => 'training', 'label' => 'Training', 'phase' => 2],
            ['key' => 'goals', 'label' => 'Goals', 'phase' => 2],
            ['key' => 'tasks', 'label' => 'Tasks', 'phase' => 3],
            ['key' => 'notes', 'label' => 'Coaching Notes', 'phase' => 3],
            ['key' => 'meetings', 'label' => 'Meetings', 'phase' => 4],
            ['key' => 'reports', 'label' => 'Reports', 'phase' => 4],
            ['key' => 'risk', 'label' => 'Risk', 'phase' => 5],
            ['key' => 'promotion', 'label' => 'Promotion', 'phase' => 5],
            ['key' => 'assistant', 'label' => 'AI Coach', 'phase' => 6],
        ];
    }
}
