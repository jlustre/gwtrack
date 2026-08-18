<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use App\Models\User;
use App\Services\Goals\GoalService;
use App\Services\Goals\SmartGoalValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GoalWizard extends Component
{
    public int $step = 1;

    public ?int $goalCategoryId = null;

    public ?int $goalTemplateId = null;

    public string $name = '';

    public string $description = '';

    public string $hierarchyLevel = 'monthly';

    public string $measurementType = 'number';

    public ?string $metricKey = null;

    public string $targetValue = '';

    public ?string $startsAt = null;

    public ?string $deadlineAt = null;

    /** @var list<array{name: string, due_at: string, target_value: string}> */
    public array $milestones = [];

    public ?int $accountabilityPartnerId = null;

    public ?int $parentGoalId = null;

    public bool $notifyEmail = true;

    public bool $notifyInApp = true;

    public bool $remindWeekly = false;

    public int $smartScore = 0;

    /** @var list<array{key: string, label: string, passed: bool, suggestion: string|null}> */
    public array $smartFeedback = [];

    public function mount(?int $template = null): void
    {
        $this->authorize('create', Goal::class);
        $this->startsAt = now()->toDateString();

        if ($template) {
            $this->applyTemplate($template);
        }
    }

    public function updatedGoalCategoryId(): void
    {
        $this->goalTemplateId = null;
        $this->evaluateSmart();
    }

    public function selectCategory(int $categoryId): void
    {
        $this->goalCategoryId = $categoryId;
        $this->updatedGoalCategoryId();
    }

    public function selectMeasurementType(string $type): void
    {
        $this->measurementType = $type;
        $this->evaluateSmart();
    }

    public function applyTemplate(int $templateId): void
    {
        $template = GoalTemplate::query()->with('category')->findOrFail($templateId);

        $this->goalTemplateId = $template->id;
        $this->goalCategoryId = $template->goal_category_id;
        $this->name = $template->name;
        $this->description = $template->description ?? '';
        $this->hierarchyLevel = $template->hierarchy_level;
        $this->measurementType = $template->measurement_type;
        $this->metricKey = $template->metric_key;
        $this->targetValue = (string) ($template->default_target ?? '');

        if (is_array($template->suggested_milestones)) {
            $this->milestones = collect($template->suggested_milestones)
                ->map(fn (array|string $m): array => is_array($m)
                    ? ['name' => $m['name'] ?? '', 'due_at' => $m['due_at'] ?? '', 'target_value' => (string) ($m['target_value'] ?? '')]
                    : ['name' => (string) $m, 'due_at' => '', 'target_value' => ''])
                ->all();
        }

        $this->evaluateSmart();
    }

    public function addMilestone(): void
    {
        $this->milestones[] = ['name' => '', 'due_at' => '', 'target_value' => ''];
    }

    public function removeMilestone(int $index): void
    {
        unset($this->milestones[$index]);
        $this->milestones = array_values($this->milestones);
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->evaluateSmart();
        $this->step = min(9, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(GoalService $goalService): void
    {
        $this->validateCurrentStep();

        $goal = $goalService->create(auth()->user(), [
            'goal_category_id' => $this->goalCategoryId,
            'goal_template_id' => $this->goalTemplateId,
            'parent_goal_id' => $this->parentGoalId,
            'hierarchy_level' => $this->hierarchyLevel,
            'name' => $this->name,
            'description' => $this->description,
            'measurement_type' => $this->measurementType,
            'metric_key' => $this->metricKey,
            'target_value' => (float) $this->targetValue,
            'starts_at' => $this->startsAt,
            'deadline_at' => $this->deadlineAt,
            'accountability_partner_id' => $this->accountabilityPartnerId,
            'notification_settings' => [
                'email' => $this->notifyEmail,
                'in_app' => $this->notifyInApp,
                'remind_weekly' => $this->remindWeekly,
            ],
        ], $this->normalizedMilestones(), []);

        session()->flash('goals_status', "Goal \"{$goal->name}\" created successfully.");

        $this->dispatch('goals-goal-created');
    }

    public function evaluateSmart(): void
    {
        $result = app(SmartGoalValidator::class)->evaluate([
            'name' => $this->name,
            'description' => $this->description,
            'target_value' => $this->targetValue,
            'measurement_type' => $this->measurementType,
            'deadline_at' => $this->deadlineAt,
            'starts_at' => $this->startsAt,
            'metric_key' => $this->metricKey,
            'goal_category_id' => $this->goalCategoryId,
        ]);

        $this->smartScore = $result['score'];
        $this->smartFeedback = $result['feedback'];
    }

    public function render(): View
    {
        $category = $this->goalCategoryId
            ? GoalCategory::query()->find($this->goalCategoryId)
            : null;

        $metrics = collect(config('goals.metrics', []))
            ->when($category, fn ($c) => $c->filter(fn (array $m) => ($m['category'] ?? '') === $category->slug))
            ->all();

        return view('livewire.goals.goal-wizard', [
            'categories' => GoalCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'templates' => $this->goalCategoryId
                ? GoalTemplate::query()->where('goal_category_id', $this->goalCategoryId)->where('is_active', true)->orderBy('sort_order')->get()
                : collect(),
            'hierarchyLevels' => config('goals.hierarchy_levels', []),
            'measurementTypes' => config('goals.measurement_types', []),
            'metrics' => $metrics,
            'fieldHelp' => config('goals-planning.wizard_fields', []),
            'partners' => $this->accountabilityPartnerOptions(),
            'parentGoals' => Goal::query()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['active', 'draft'])
                ->where('id', '!=', $this->parentGoalId)
                ->orderBy('name')
                ->get(['id', 'name', 'hierarchy_level']),
        ]);
    }

    private function validateCurrentStep(): void
    {
        match ($this->step) {
            1 => $this->validate(['goalCategoryId' => ['required', 'exists:goal_categories,id']]),
            2 => $this->validate(['name' => ['required', 'string', 'min:3', 'max:255']]),
            3 => $this->validate(['targetValue' => ['required', 'numeric', 'min:0']]),
            4 => $this->validate(['measurementType' => ['required', Rule::in(array_keys(config('goals.measurement_types', [])))]]),
            5 => $this->validate(['deadlineAt' => ['required', 'date', 'after_or_equal:startsAt']]),
            default => null,
        };
    }

    /**
     * @return list<array{name: string, due_at?: string|null, target_value?: float|null}>
     */
    private function normalizedMilestones(): array
    {
        return collect($this->milestones)
            ->filter(fn (array $m) => filled($m['name'] ?? null))
            ->map(fn (array $m): array => [
                'name' => $m['name'],
                'due_at' => filled($m['due_at'] ?? null) ? $m['due_at'] : null,
                'target_value' => filled($m['target_value'] ?? null) ? (float) $m['target_value'] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function accountabilityPartnerOptions()
    {
        $user = auth()->user()->loadMissing(['sponsor', 'mentor']);

        return collect([$user->sponsor, $user->mentor])
            ->filter()
            ->unique('id')
            ->values();
    }
}
