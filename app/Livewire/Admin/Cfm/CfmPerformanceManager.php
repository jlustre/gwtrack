<?php

namespace App\Livewire\Admin\Cfm;

use App\Models\CfmEffectiveness\CfmFeedbackQuestion;
use App\Models\CfmEffectiveness\CfmFeedbackResponse;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessFeedbackService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class CfmPerformanceManager extends Component
{
    public string $tab = 'aspects';

    public string $questionText = '';

    public string $questionCategory = 'communication';

    public ?int $editingQuestionId = null;

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('manage mentor reviews')
            || auth()->user()?->hasAnyRole(['super-admin', 'admin', 'agency-owner']),
            403
        );
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['aspects', 'scores', 'requests'], true) ? $tab : 'aspects';
        $this->statusMessage = '';
    }

    public function editQuestion(int $questionId): void
    {
        $question = CfmFeedbackQuestion::query()->findOrFail($questionId);
        $this->editingQuestionId = $question->id;
        $this->questionText = $question->question;
        $this->questionCategory = $question->category ?: 'mentorship';
    }

    public function cancelEdit(): void
    {
        $this->resetQuestionForm();
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'questionText' => ['required', 'string', 'max:255'],
            'questionCategory' => ['required', 'string', 'max:50'],
        ]);

        $categories = array_keys(config('cfm-effectiveness.mentoring_aspect_categories', []));
        abort_unless(in_array($this->questionCategory, $categories, true) || $this->questionCategory === 'mentorship', 422);

        if ($this->editingQuestionId) {
            CfmFeedbackQuestion::query()->whereKey($this->editingQuestionId)->update([
                'question' => trim($this->questionText),
                'category' => $this->questionCategory,
            ]);
            $this->statusMessage = 'Mentoring aspect updated.';
        } else {
            $maxSort = (int) CfmFeedbackQuestion::query()->max('sort_order');
            $baseKey = Str::slug($this->questionText, '_');
            $key = $baseKey !== '' ? $baseKey : 'aspect_'.Str::lower(Str::random(6));
            $suffix = 1;
            while (CfmFeedbackQuestion::query()->where('key', $key)->exists()) {
                $key = $baseKey.'_'.$suffix;
                $suffix++;
            }

            CfmFeedbackQuestion::query()->create([
                'key' => $key,
                'question' => trim($this->questionText),
                'category' => $this->questionCategory,
                'sort_order' => $maxSort + 10,
                'is_active' => true,
            ]);
            $this->statusMessage = 'Mentoring aspect added.';
        }

        $this->resetQuestionForm();
    }

    public function toggleQuestion(int $questionId): void
    {
        $question = CfmFeedbackQuestion::query()->findOrFail($questionId);
        $question->update(['is_active' => ! $question->is_active]);
        $this->statusMessage = $question->is_active ? 'Aspect activated.' : 'Aspect deactivated.';
    }

    public function moveQuestion(int $questionId, string $direction): void
    {
        $question = CfmFeedbackQuestion::query()->findOrFail($questionId);
        $swap = CfmFeedbackQuestion::query()
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $question->sort_order)->orderByDesc('sort_order'))
            ->when($direction !== 'up', fn ($q) => $q->where('sort_order', '>', $question->sort_order)->orderBy('sort_order'))
            ->first();

        if (! $swap) {
            return;
        }

        $currentSort = $question->sort_order;
        $question->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $currentSort]);
    }

    public function requestReviewForAssignment(int $assignmentId, CfmEffectivenessFeedbackService $feedback): void
    {
        $assignment = MentorAssignment::query()->with(['mentor', 'apprentice'])->findOrFail($assignmentId);

        $existingPending = CfmReview::query()
            ->where('cfm_id', $assignment->mentor_id)
            ->where('trainee_id', $assignment->apprentice_id)
            ->where('status', 'pending')
            ->exists();

        $feedback->requestAdminEvaluation($assignment, auth()->user());
        $this->tab = 'requests';
        $this->statusMessage = $existingPending
            ? "Pending evaluation already exists for {$assignment->apprentice?->name}."
            : "Evaluation request created for {$assignment->apprentice?->name} → {$assignment->mentor?->name}.";
    }

    public function render(): View
    {
        $categories = config('cfm-effectiveness.mentoring_aspect_categories', []);
        $questions = CfmFeedbackQuestion::query()->orderBy('sort_order')->orderBy('id')->get();

        $responseStats = CfmFeedbackResponse::query()
            ->selectRaw('question_id, AVG(rating) as avg_rating, COUNT(*) as response_count')
            ->groupBy('question_id')
            ->get()
            ->keyBy('question_id');

        $cfmScoreRows = CfmReview::query()
            ->select('cfm_id', DB::raw('AVG(average_rating) as avg_rating'), DB::raw('COUNT(*) as review_count'))
            ->where('status', 'submitted')
            ->whereNotNull('average_rating')
            ->groupBy('cfm_id')
            ->orderByDesc('avg_rating')
            ->limit(40)
            ->get();

        $cfmUsers = User::query()
            ->with('profile')
            ->whereIn('id', $cfmScoreRows->pluck('cfm_id'))
            ->get()
            ->keyBy('id');

        $cfmScores = $cfmScoreRows->map(fn ($row) => [
            'cfm' => $cfmUsers->get($row->cfm_id),
            'avg_rating' => round((float) $row->avg_rating, 2),
            'review_count' => (int) $row->review_count,
        ]);

        $activeAssignments = MentorAssignment::query()
            ->with(['mentor.profile', 'apprentice.profile'])
            ->where('status', 'active')
            ->latest('id')
            ->limit(50)
            ->get();

        $pendingReviews = CfmReview::query()
            ->with(['cfm', 'trainee'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(40)
            ->get();

        return view('livewire.admin.cfm.cfm-performance-manager', [
            'categories' => $categories,
            'questions' => $questions,
            'questionsByCategory' => $questions->groupBy(fn ($q) => $q->category ?: 'mentorship'),
            'responseStats' => $responseStats,
            'cfmScores' => $cfmScores,
            'activeAssignments' => $activeAssignments,
            'pendingReviews' => $pendingReviews,
            'agencyAverage' => round((float) (CfmReview::query()->where('status', 'submitted')->avg('average_rating') ?? 0), 2),
            'submittedCount' => CfmReview::query()->where('status', 'submitted')->count(),
            'pendingCount' => CfmReview::query()->where('status', 'pending')->count(),
            'activeAspectCount' => $questions->where('is_active', true)->count(),
        ])->layout('layouts.app');
    }

    private function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->questionText = '';
        $this->questionCategory = 'communication';
    }
}
