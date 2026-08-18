<?php

namespace App\Services\Training;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\MentorAssignment;
use App\Models\TrainingAssignment;
use App\Models\TrainingCertification;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserTrainingCertification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingCertificationService
{
    public function __construct(
        private readonly TrainingCoursePlayerService $courses,
        private readonly TrainingAssessmentService $assessments,
    ) {}

    /**
     * @return Collection<int, UserTrainingCertification>
     */
    public function certificationsFor(User $user): Collection
    {
        return UserTrainingCertification::query()
            ->with(['certification.module', 'approvedBy'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->get();
    }

    /**
     * @return list<array{
     *     record: UserTrainingCertification,
     *     certification: TrainingCertification,
     *     status_label: string
     * }>
     */
    public function certificationRowsFor(User $user): array
    {
        return $this->certificationsFor($user)->map(function (UserTrainingCertification $record): array {
            return [
                'record' => $record,
                'certification' => $record->certification,
                'status_label' => str($record->status)->replace('_', ' ')->title(),
            ];
        })->all();
    }

    /**
     * @return Collection<int, UserTrainingCertification>
     */
    public function pendingReviewsFor(User $reviewer): Collection
    {
        $traineeIds = MentorAssignment::query()
            ->where('mentor_id', $reviewer->id)
            ->where('status', 'active')
            ->pluck('apprentice_id');

        $query = UserTrainingCertification::query()
            ->with(['user', 'certification.module'])
            ->where('status', 'pending');

        if ($reviewer->can('manage training')) {
            return $query->latest()->get();
        }

        return $query
            ->whereIn('user_id', $traineeIds)
            ->latest()
            ->get();
    }

    public function processAssessmentPassed(User $user, Assessment $assessment): void
    {
        $certifications = TrainingCertification::query()
            ->where('is_active', true)
            ->where(function ($query) use ($assessment): void {
                $query->where('assessment_id', $assessment->id);

                if ($assessment->training_module_id) {
                    $query->orWhere('training_module_id', $assessment->training_module_id);
                }
            })
            ->get();

        foreach ($certifications as $certification) {
            $this->syncForUser($user, $certification);
        }
    }

    public function processCourseCompleted(User $user, TrainingModule $module): void
    {
        if ($this->courses->moduleProgressPercent($user, $module) < 100) {
            return;
        }

        $certifications = TrainingCertification::query()
            ->where('is_active', true)
            ->where('training_module_id', $module->id)
            ->get();

        foreach ($certifications as $certification) {
            $this->syncForUser($user, $certification);
        }
    }

    public function syncForUser(User $user, TrainingCertification $certification): ?UserTrainingCertification
    {
        $existing = UserTrainingCertification::query()
            ->where('user_id', $user->id)
            ->where('training_certification_id', $certification->id)
            ->first();

        if ($existing && in_array($existing->status, ['issued', 'pending'], true)) {
            return $existing;
        }

        $eligibility = $this->eligibility($user, $certification);

        if (! $eligibility['eligible']) {
            return null;
        }

        if ($certification->mentor_approval_required) {
            return UserTrainingCertification::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'training_certification_id' => $certification->id,
                ],
                [
                    'status' => 'pending',
                ],
            );
        }

        return $this->issue($user, $certification, $existing);
    }

    /**
     * @return array{eligible: bool, reason: string|null}
     */
    public function eligibility(User $user, TrainingCertification $certification): array
    {
        if ($certification->training_module_id) {
            $module = $certification->module()->with('lessons')->first();

            if ($module && $this->courses->moduleProgressPercent($user, $module) < 100) {
                return ['eligible' => false, 'reason' => 'course_incomplete'];
            }
        }

        $assessment = $this->linkedAssessment($certification);

        if ($assessment) {
            $bestAttempt = AssessmentAttempt::query()
                ->where('user_id', $user->id)
                ->where('assessment_id', $assessment->id)
                ->where('passed', true)
                ->orderByDesc('score')
                ->first();

            if (! $bestAttempt || $bestAttempt->score < $certification->required_score) {
                return ['eligible' => false, 'reason' => 'assessment_required'];
            }
        }

        return ['eligible' => true, 'reason' => null];
    }

    public function canApprove(User $approver, UserTrainingCertification $record): bool
    {
        if ($approver->can('manage training')) {
            return true;
        }

        return MentorAssignment::query()
            ->where('mentor_id', $approver->id)
            ->where('apprentice_id', $record->user_id)
            ->where('status', 'active')
            ->exists();
    }

    public function approve(UserTrainingCertification $record, User $approver): UserTrainingCertification
    {
        abort_unless($record->status === 'pending', 422);
        abort_unless($this->canApprove($approver, $record), 403);

        $record->update([
            'status' => 'issued',
            'approved_by' => $approver->id,
            'certificate_number' => $record->certificate_number ?? $this->generateCertificateNumber(),
            'issued_at' => now(),
            'expires_at' => $this->expirationDate(),
        ]);

        app(TrainingGamificationService::class)->recordCertificationIssued($record->user, $record->fresh());

        return $record->fresh(['certification.module', 'user', 'approvedBy']);
    }

    public function reject(UserTrainingCertification $record, User $approver): UserTrainingCertification
    {
        abort_unless($record->status === 'pending', 422);
        abort_unless($this->canApprove($approver, $record), 403);

        $record->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
        ]);

        return $record->fresh(['certification.module', 'user', 'approvedBy']);
    }

    private function issue(
        User $user,
        TrainingCertification $certification,
        ?UserTrainingCertification $existing = null,
    ): UserTrainingCertification {
        $record = $existing ?? UserTrainingCertification::query()->firstOrNew([
            'user_id' => $user->id,
            'training_certification_id' => $certification->id,
        ]);

        $record->fill([
            'status' => 'issued',
            'certificate_number' => $record->certificate_number ?? $this->generateCertificateNumber(),
            'issued_at' => now(),
            'expires_at' => $this->expirationDate(),
        ]);
        $record->save();

        app(TrainingGamificationService::class)->recordCertificationIssued($user, $record->fresh());

        return $record->fresh(['certification.module', 'approvedBy']);
    }

    private function linkedAssessment(TrainingCertification $certification): ?Assessment
    {
        if ($certification->assessment_id) {
            return $certification->assessment;
        }

        if (! $certification->training_module_id) {
            return null;
        }

        return Assessment::query()
            ->published()
            ->where('training_module_id', $certification->training_module_id)
            ->whereHas('questions')
            ->first();
    }

    private function generateCertificateNumber(): string
    {
        $prefix = config('training-academy.certifications.certificate_prefix', 'GW');
        $year = now()->format('Y');
        $sequence = UserTrainingCertification::query()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    private function expirationDate(): ?CarbonInterface
    {
        $years = config('training-academy.certifications.validity_years');

        return $years ? now()->addYears((int) $years) : null;
    }
}
