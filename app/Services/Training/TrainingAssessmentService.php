<?php

namespace App\Services\Training;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingAssessmentService
{
    public function __construct(private readonly TrainingCoursePlayerService $courses) {}

    /**
     * @return Collection<int, Assessment>
     */
    public function publishedAssessments(): Collection
    {
        return Assessment::query()
            ->published()
            ->with(['module.category'])
            ->whereHas('questions')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return list<array{
     *     assessment: Assessment,
     *     attempts_count: int,
     *     best_score: int|null,
     *     passed: bool,
     *     can_take: bool,
     *     lock_reason: string|null
     * }>
     */
    public function assessmentRowsFor(User $user): array
    {
        return $this->publishedAssessments()->map(function (Assessment $assessment) use ($user): array {
            $stats = $this->attemptStats($user, $assessment);
            $access = $this->canTake($user, $assessment);

            return [
                'assessment' => $assessment,
                'attempts_count' => $stats['attempts_count'],
                'best_score' => $stats['best_score'],
                'passed' => $stats['passed'],
                'can_take' => $access['allowed'],
                'lock_reason' => $access['reason'],
            ];
        })->all();
    }

    /**
     * @return array{attempts_count: int, best_score: int|null, passed: bool, latest_attempt: AssessmentAttempt|null}
     */
    public function attemptStats(User $user, Assessment $assessment): array
    {
        $attempts = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();

        $passed = $attempts->contains(fn (AssessmentAttempt $attempt) => $attempt->passed);

        return [
            'attempts_count' => $attempts->count(),
            'best_score' => $attempts->max('score'),
            'passed' => $passed,
            'latest_attempt' => $attempts->first(),
        ];
    }

    /**
     * @return array{allowed: bool, reason: string|null}
     */
    public function canTake(User $user, Assessment $assessment): array
    {
        abort_unless($assessment->is_published, 404);
        abort_unless($assessment->hasQuestions(), 404);

        $stats = $this->attemptStats($user, $assessment);
        $maxAttempts = config('training-academy.assessments.max_attempts');

        if ($stats['passed'] && ! config('training-academy.assessments.allow_retakes_after_pass')) {
            return ['allowed' => false, 'reason' => 'passed'];
        }

        if ($maxAttempts !== null && $stats['attempts_count'] >= $maxAttempts) {
            return ['allowed' => false, 'reason' => 'max_attempts'];
        }

        if ($assessment->training_module_id && config('training-academy.assessments.require_course_completion')) {
            $module = $assessment->module()->with('lessons')->first();

            if ($module && $this->courses->moduleProgressPercent($user, $module) < 100) {
                return ['allowed' => false, 'reason' => 'course_incomplete'];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * @return Collection<int, Question>
     */
    public function questionsForTaking(Assessment $assessment): Collection
    {
        return $assessment->questions()
            ->with(['answers' => fn ($query) => $query->orderBy('id')])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<int|string, array{answer_id?: int, text?: string}>  $responses
     */
    public function submitAttempt(User $user, Assessment $assessment, array $responses): AssessmentAttempt
    {
        $access = $this->canTake($user, $assessment);

        if (! $access['allowed']) {
            throw ValidationException::withMessages([
                'assessment' => match ($access['reason']) {
                    'passed' => 'You have already passed this assessment.',
                    'max_attempts' => 'You have used all available attempts.',
                    'course_incomplete' => 'Complete the linked course before taking this assessment.',
                    default => 'You cannot take this assessment right now.',
                },
            ]);
        }

        $questions = $this->questionsForTaking($assessment);
        $this->validateResponses($questions, $responses);

        $graded = $this->gradeResponses($questions, $responses);
        $score = $questions->isEmpty()
            ? 0
            : (int) round(($graded['correct'] / $questions->count()) * 100);
        $passed = $score >= $assessment->passing_score;

        $attempt = AssessmentAttempt::query()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'score' => $score,
            'passed' => $passed,
            'answers_snapshot' => $graded['snapshot'],
            'completed_at' => now(),
        ]);

        if ($passed) {
            app(TrainingCertificationService::class)->processAssessmentPassed($user, $assessment);
            app(TrainingGamificationService::class)->recordAssessmentPassed($user, $attempt);
            app(TrainingRecommendationService::class)->syncForUser($user);
        }

        return $attempt;
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<int|string, array{answer_id?: int, text?: string}>  $responses
     */
    private function validateResponses(Collection $questions, array $responses): void
    {
        foreach ($questions as $question) {
            $response = $responses[$question->id] ?? null;

            if ($question->type === 'short_answer') {
                if (! is_array($response) || blank($response['text'] ?? null)) {
                    throw ValidationException::withMessages([
                        "responses.{$question->id}" => 'Please answer every question before submitting.',
                    ]);
                }

                continue;
            }

            if (! is_array($response) || empty($response['answer_id'])) {
                throw ValidationException::withMessages([
                    "responses.{$question->id}" => 'Please answer every question before submitting.',
                ]);
            }

            $validAnswer = $question->answers->contains('id', (int) $response['answer_id']);

            if (! $validAnswer) {
                throw ValidationException::withMessages([
                    "responses.{$question->id}" => 'Please select a valid answer.',
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<int|string, array{answer_id?: int, text?: string}>  $responses
     * @return array{correct: int, snapshot: list<array<string, mixed>>}
     */
    private function gradeResponses(Collection $questions, array $responses): array
    {
        $correct = 0;
        $snapshot = [];

        foreach ($questions as $question) {
            $response = $responses[$question->id] ?? [];
            $isCorrect = $this->gradeQuestion($question, $response);

            if ($isCorrect) {
                $correct++;
            }

            $snapshot[] = [
                'question_id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'response' => $response,
                'is_correct' => $isCorrect,
            ];
        }

        return [
            'correct' => $correct,
            'snapshot' => $snapshot,
        ];
    }

    /**
     * @param  array{answer_id?: int, text?: string}  $response
     */
    private function gradeQuestion(Question $question, array $response): bool
    {
        return match ($question->type) {
            'multiple_choice', 'true_false' => $question->answers
                ->first(fn (Answer $answer) => (int) $answer->id === (int) ($response['answer_id'] ?? 0))
                ?->is_correct ?? false,
            'short_answer' => $question->answers
                ->where('is_correct', true)
                ->contains(fn (Answer $answer) => strcasecmp(
                    trim((string) ($response['text'] ?? '')),
                    trim($answer->answer),
                ) === 0),
            default => false,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attemptBreakdown(AssessmentAttempt $attempt): array
    {
        return collect($attempt->answers_snapshot ?? [])->map(function (array $row): array {
            $question = Question::query()->with('answers')->find($row['question_id'] ?? null);

            return [
                ...$row,
                'answers' => $question?->answers ?? collect(),
            ];
        })->all();
    }
}
