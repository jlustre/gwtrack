<?php

namespace App\Services\Training;

use App\Models\MentorAssignment;
use App\Models\MentorTrainingReview;
use App\Models\TrainingSession;
use App\Models\TrainingSessionAttendance;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\DashboardStatsService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingCoachingService
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly DashboardStatsService $dashboardStats,
        private readonly TrainingCalendarService $calendar,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hubFor(User $user): array
    {
        return [
            'is_mentor' => $this->isMentor($user),
            'is_trainee' => $this->isTrainee($user),
            'trainee' => $this->traineeHub($user),
            'mentor' => $this->isMentor($user) ? $this->mentorHub($user) : null,
            'sessions' => $this->sessionRowsFor($user),
            'review_types' => config('training-academy.coaching.review_types', []),
        ];
    }

    public function isMentor(User $user): bool
    {
        return $user->hasRole('certified-field-mentor')
            || $user->can('manage training')
            || MentorAssignment::query()
                ->where('mentor_id', $user->id)
                ->where('status', 'active')
                ->exists();
    }

    public function isTrainee(User $user): bool
    {
        return MentorAssignment::query()
            ->where('apprentice_id', $user->id)
            ->where('status', 'active')
            ->exists()
            || $this->checklists->hasTypeStarted($user, 'fap');
    }

    /**
     * @return array<string, mixed>
     */
    private function traineeHub(User $user): array
    {
        $assignment = MentorAssignment::query()
            ->where('apprentice_id', $user->id)
            ->where('status', 'active')
            ->with('mentor.profile')
            ->first();

        $fapStarted = $this->checklists->hasTypeStarted($user, 'fap');
        $fapPercent = $fapStarted ? $this->dashboardStats->apprenticeshipPercent($user) : 0;

        $reviews = MentorTrainingReview::query()
            ->with('mentor')
            ->where('trainee_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $signoff = $reviews->first(
            fn (MentorTrainingReview $review) => $review->review_type === 'fap_signoff' && $review->status === 'approved',
        );

        return [
            'assignment' => $assignment,
            'mentor' => $assignment?->mentor,
            'fap_started' => $fapStarted,
            'fap_percent' => $fapPercent,
            'reviews' => $reviews,
            'fap_signoff' => $signoff,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mentorHub(User $mentor): array
    {
        return [
            'trainees' => $this->traineeRowsForMentor($mentor),
            'recent_reviews' => MentorTrainingReview::query()
                ->with(['trainee', 'module'])
                ->where('mentor_id', $mentor->id)
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function traineeRowsForMentor(User $mentor): array
    {
        return MentorAssignment::query()
            ->where('mentor_id', $mentor->id)
            ->where('status', 'active')
            ->with(['apprentice.rank'])
            ->orderBy('started_at')
            ->get()
            ->map(function (MentorAssignment $assignment) use ($mentor): array {
                $trainee = $assignment->apprentice;
                abort_unless($trainee, 500);

                $fapStarted = $this->checklists->hasTypeStarted($trainee, 'fap');
                $fapPercent = $fapStarted ? $this->dashboardStats->apprenticeshipPercent($trainee) : 0;

                return [
                    'assignment' => $assignment,
                    'trainee' => $trainee,
                    'fap_started' => $fapStarted,
                    'fap_percent' => $fapPercent,
                    'can_review' => $this->canReviewTrainee($mentor, $trainee),
                    'can_sign_off' => $this->canSignOffFap($mentor, $trainee),
                    'has_signoff' => $this->hasApprovedSignoff($trainee),
                ];
            })
            ->all();
    }

    public function canReviewTrainee(User $mentor, User $trainee): bool
    {
        if ($mentor->can('manage training')) {
            return true;
        }

        return MentorAssignment::query()
            ->where('mentor_id', $mentor->id)
            ->where('apprentice_id', $trainee->id)
            ->where('status', 'active')
            ->exists();
    }

    public function canSignOffFap(User $mentor, User $trainee): bool
    {
        if (! $this->canReviewTrainee($mentor, $trainee)) {
            return false;
        }

        if ($this->hasApprovedSignoff($trainee)) {
            return false;
        }

        if (! $this->checklists->hasTypeStarted($trainee, 'fap')) {
            return false;
        }

        $minPercent = (int) config('training-academy.coaching.fap_signoff_min_percent', 90);

        return $this->dashboardStats->apprenticeshipPercent($trainee) >= $minPercent;
    }

    public function hasApprovedSignoff(User $trainee): bool
    {
        return MentorTrainingReview::query()
            ->where('trainee_id', $trainee->id)
            ->where('review_type', 'fap_signoff')
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * @param  array{review_type: string, score?: int|null, feedback?: string|null, training_module_id?: int|null}  $data
     */
    public function submitReview(User $mentor, User $trainee, array $data): MentorTrainingReview
    {
        abort_unless($this->canReviewTrainee($mentor, $trainee), 403);

        $reviewType = $data['review_type'] ?? 'coaching';
        $allowedTypes = array_keys(config('training-academy.coaching.review_types', []));

        if (! in_array($reviewType, $allowedTypes, true) || $reviewType === 'fap_signoff') {
            throw ValidationException::withMessages([
                'review_type' => 'Please choose a valid review type.',
            ]);
        }

        return MentorTrainingReview::query()->create([
            'mentor_id' => $mentor->id,
            'trainee_id' => $trainee->id,
            'training_module_id' => $data['training_module_id'] ?? null,
            'review_type' => $reviewType,
            'score' => $data['score'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'status' => 'submitted',
        ]);
    }

    public function signOffFap(User $mentor, User $trainee, ?string $feedback = null, ?int $score = null): MentorTrainingReview
    {
        if (! $this->canSignOffFap($mentor, $trainee)) {
            throw ValidationException::withMessages([
                'signoff' => 'FAP sign-off is not available for this trainee yet.',
            ]);
        }

        return MentorTrainingReview::query()->create([
            'mentor_id' => $mentor->id,
            'trainee_id' => $trainee->id,
            'review_type' => 'fap_signoff',
            'score' => $score,
            'feedback' => $feedback,
            'status' => 'approved',
        ]);
    }

    /**
     * @return list<array{session: TrainingSession, registered: bool, seats_remaining: int|null}>
     */
    public function sessionRowsFor(User $user): array
    {
        return TrainingSession::query()
            ->upcoming()
            ->with(['instructor', 'module', 'calendarEvent'])
            ->withCount('attendance')
            ->limit(12)
            ->get()
            ->map(function (TrainingSession $session) use ($user): array {
                $registered = TrainingSessionAttendance::query()
                    ->where('training_session_id', $session->id)
                    ->where('user_id', $user->id)
                    ->exists();

                $seatsRemaining = $session->capacity
                    ? max(0, $session->capacity - $session->attendance_count)
                    : null;

                return [
                    'session' => $session,
                    'registered' => $registered,
                    'seats_remaining' => $seatsRemaining,
                    'calendar_url' => $session->calendar_event_id
                        ? route('calendar.events.show', $session->calendar_event_id)
                        : null,
                ];
            })
            ->all();
    }

    public function registerForSession(User $user, TrainingSession $session): TrainingSessionAttendance
    {
        abort_unless($session->is_active && $session->starts_at?->isFuture(), 422);

        $count = $session->attendance()->count();

        if ($session->capacity !== null && $count >= $session->capacity) {
            throw ValidationException::withMessages([
                'session' => 'This session is full.',
            ]);
        }

        $attendance = TrainingSessionAttendance::query()->firstOrCreate(
            [
                'training_session_id' => $session->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'registered',
            ],
        );

        $this->calendar->syncAttendanceToCalendar($attendance);

        return $attendance;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSession(User $instructor, array $data): TrainingSession
    {
        abort_unless($this->isMentor($instructor) || $instructor->can('manage training'), 403);

        $session = TrainingSession::query()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'session_type' => $data['session_type'] ?? 'live',
            'training_module_id' => $data['training_module_id'] ?? null,
            'instructor_id' => $instructor->id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'is_active' => true,
        ]);

        $this->calendar->syncSessionToCalendar($session);

        return $session->fresh(['instructor', 'module', 'calendarEvent']);
    }

    /**
     * @return Collection<int, User>
     */
    public function mentorTrainees(User $mentor): Collection
    {
        return MentorAssignment::query()
            ->where('mentor_id', $mentor->id)
            ->where('status', 'active')
            ->with('apprentice')
            ->get()
            ->pluck('apprentice')
            ->filter();
    }
}
