<?php

namespace App\Livewire\Admin;

use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\UserRankProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class RankAdministrationManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $trackFilter = '';

    public ?int $selectedRankId = null;

    public bool $showRankForm = false;

    public ?int $editingRankId = null;

    /** @var array<string, mixed> */
    public array $rankForm = [];

    public ?int $editingRequirementId = null;

    /** @var array<string, mixed> */
    public array $requirementForm = [];

    public function mount(): void
    {
        $this->authorizeAdministration();
        $this->resetRankForm();
        $this->resetRequirementForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTrackFilter(): void
    {
        $this->resetPage();
    }

    public function selectRank(int $rankId): void
    {
        $this->authorizeAdministration();
        $this->selectedRankId = Rank::query()->whereKey($rankId)->value('id');
        abort_if($this->selectedRankId === null, 404);
        $this->resetRequirementForm();
    }

    public function createRank(): void
    {
        $this->authorizeAdministration();
        $this->editingRankId = null;
        $this->rankForm = $this->defaultRankForm();
        $this->showRankForm = true;
    }

    public function editRank(int $rankId): void
    {
        $this->authorizeAdministration();
        $rank = Rank::query()->findOrFail($rankId);
        $this->editingRankId = $rank->id;
        $this->rankForm = $rank->only(array_keys($this->defaultRankForm()));
        $this->showRankForm = true;
    }

    public function saveRank(): void
    {
        $this->authorizeAdministration();

        $rankId = $this->editingRankId;
        $validated = $this->validate([
            'rankForm.code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('ranks', 'code')->whereNull('deleted_at')->ignore($rankId),
            ],
            'rankForm.name' => ['required', 'string', 'max:255'],
            'rankForm.sort_order' => [
                'required',
                'integer',
                'between:0,65535',
                Rule::unique('ranks', 'sort_order')->whereNull('deleted_at')->ignore($rankId),
            ],
            'rankForm.track' => ['required', Rule::in(['producer', 'builder'])],
            'rankForm.income_metric' => ['nullable', Rule::in(['', 'lti', 'twelve_month_income'])],
            'rankForm.income_requirement' => ['nullable', 'integer', 'min:0'],
            'rankForm.qualification' => ['nullable', 'string', 'max:255'],
            'rankForm.nba_requirement' => ['nullable', 'integer', 'min:0'],
            'rankForm.mb_leg_requirement' => ['nullable', 'integer', 'min:0'],
            'rankForm.svp_leg_requirement' => ['nullable', 'integer', 'min:0'],
            'rankForm.svp_leg_alternate' => ['nullable', 'integer', 'min:0'],
            'rankForm.compensation_rate' => ['nullable', 'numeric', 'between:0,500'],
            'rankForm.ppb_rate' => ['nullable', 'numeric', 'between:0,500'],
            'rankForm.total_fyo_rate' => ['nullable', 'numeric', 'between:0,500'],
            'rankForm.non_fyc_rate' => ['nullable', 'numeric', 'between:0,500'],
            'rankForm.is_field_leader' => ['required', 'boolean'],
            'rankForm.description' => ['nullable', 'string', 'max:2000'],
            'rankForm.is_active' => ['required', 'boolean'],
        ]);
        $attributes = $validated['rankForm'];
        $attributes['income_metric'] = filled($attributes['income_metric']) ? $attributes['income_metric'] : null;

        if ($rankId !== null) {
            $rank = Rank::query()->findOrFail($rankId);
            abort_unless($this->rankForm['code'] === $rank->code, 422, 'Rank codes cannot be changed after creation.');
            $this->guardEntryRankActivation($rank, (bool) $this->rankForm['is_active']);
            $rank->update($attributes);
        } else {
            $rank = Rank::query()->create($attributes);
            $this->selectedRankId = $rank->id;
        }

        $this->showRankForm = false;
        $this->editingRankId = null;
        $this->resetRankForm();
        session()->flash('rank-admin-status', 'Rank settings saved.');
    }

    public function toggleRankActive(int $rankId): void
    {
        $this->authorizeAdministration();
        $rank = Rank::query()->findOrFail($rankId);
        $nextActive = ! $rank->is_active;
        $this->guardEntryRankActivation($rank, $nextActive);
        $rank->update(['is_active' => $nextActive]);
        session()->flash('rank-admin-status', $nextActive ? 'Rank activated.' : 'Rank deactivated. Existing member assignments and history were retained.');
    }

    public function moveRank(int $rankId, string $direction): void
    {
        $this->authorizeAdministration();
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        DB::transaction(function () use ($rankId, $direction): void {
            $orderedRanks = Rank::query()->orderBy('sort_order')->orderBy('id')->get();
            $rankIndex = $orderedRanks->search(fn (Rank $rank): bool => $rank->id === $rankId);
            abort_if($rankIndex === false, 404);

            $neighborIndex = $direction === 'up' ? $rankIndex - 1 : $rankIndex + 1;

            if ($neighborIndex < 0 || $neighborIndex >= $orderedRanks->count()) {
                return;
            }

            $rank = $orderedRanks[$rankIndex];
            $neighbor = $orderedRanks[$neighborIndex];
            $rankOrder = $rank->sort_order;

            $rank->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $rankOrder]);
        });

        session()->flash('rank-admin-status', 'Rank ladder order updated.');
    }

    public function saveRequirement(): void
    {
        $this->authorizeAdministration();
        $rank = $this->selectedRank();
        $categories = array_keys(config('rank-advancement.categories', []));

        $validated = $this->validate([
            'requirementForm.title' => ['required', 'string', 'max:255'],
            'requirementForm.description' => ['nullable', 'string', 'max:2000'],
            'requirementForm.category' => ['required', Rule::in($categories)],
            'requirementForm.is_required' => ['required', 'boolean'],
            'requirementForm.sort_order' => ['required', 'integer', 'between:0,65535'],
            'requirementForm.is_active' => ['required', 'boolean'],
        ]);

        $attributes = $validated['requirementForm'];

        if ($this->editingRequirementId !== null) {
            $requirement = RankRequirement::query()
                ->where('rank_id', $rank->id)
                ->findOrFail($this->editingRequirementId);
            $requirement->update($attributes);
        } else {
            $code = substr(Str::slug($attributes['title']), 0, 140);
            $code = $code !== '' ? $code : 'custom-'.Str::uuid();

            if (RankRequirement::query()->where('rank_id', $rank->id)->where('code', $code)->exists()) {
                $this->addError('requirementForm.title', 'A requirement with this title already exists for this rank.');

                return;
            }

            $rank->requirements()->create([
                ...$attributes,
                'code' => $code,
            ]);
        }

        $this->resetRequirementForm();
        session()->flash('rank-admin-status', 'Advancement requirement saved.');
    }

    public function editRequirement(int $requirementId): void
    {
        $this->authorizeAdministration();
        $requirement = RankRequirement::query()
            ->where('rank_id', $this->selectedRank()->id)
            ->findOrFail($requirementId);
        $this->editingRequirementId = $requirement->id;
        $this->requirementForm = $requirement->only(array_keys($this->defaultRequirementForm()));
    }

    public function toggleRequirementActive(int $requirementId): void
    {
        $this->authorizeAdministration();
        $requirement = RankRequirement::query()
            ->where('rank_id', $this->selectedRank()->id)
            ->findOrFail($requirementId);
        $requirement->update(['is_active' => ! $requirement->is_active]);
        $this->resetRequirementForm();
        session()->flash('rank-admin-status', $requirement->is_active
            ? 'Requirement activated. Existing progress records remain available.'
            : 'Requirement retired. Existing progress records were retained.');
    }

    public function cancelRankForm(): void
    {
        $this->authorizeAdministration();
        $this->showRankForm = false;
        $this->editingRankId = null;
        $this->resetRankForm();
    }

    public function cancelRequirementForm(): void
    {
        $this->authorizeAdministration();
        $this->resetRequirementForm();
    }

    public function render(): View
    {
        $this->authorizeAdministration();

        $ranks = Rank::query()
            ->withCount(['users', 'requirements'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            }))
            ->when($this->trackFilter !== '', fn ($query) => $query->where('track', $this->trackFilter))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        $selectedRank = $this->selectedRank();
        $requirements = $selectedRank
            ? $selectedRank->requirements()
                ->withCount('progressRecords')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
            : collect();

        return view('livewire.admin.rank-administration-manager', [
            'ranks' => $ranks,
            'selectedRank' => $selectedRank,
            'requirements' => $requirements,
            'categories' => config('rank-advancement.categories', []),
            'activeRankCount' => Rank::query()->where('is_active', true)->count(),
            'memberCount' => UserRankProgress::query()->distinct('user_id')->count('user_id'),
        ])->layout('layouts.app');
    }

    private function authorizeAdministration(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage ranks'), 403);
    }

    private function guardEntryRankActivation(Rank $rank, bool $isActive): void
    {
        abort_if(
            ! $isActive && $rank->code === config('greatway-compensation.entry_rank', 'TA'),
            422,
            'The entry rank must remain active.',
        );
    }

    private function selectedRank(): ?Rank
    {
        return $this->selectedRankId === null
            ? null
            : Rank::query()->withCount(['users', 'requirements'])->find($this->selectedRankId);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultRankForm(): array
    {
        return [
            'code' => '',
            'name' => '',
            'sort_order' => 0,
            'track' => 'producer',
            'income_metric' => '',
            'income_requirement' => null,
            'qualification' => '',
            'nba_requirement' => null,
            'mb_leg_requirement' => null,
            'svp_leg_requirement' => null,
            'svp_leg_alternate' => null,
            'compensation_rate' => null,
            'ppb_rate' => null,
            'total_fyo_rate' => null,
            'non_fyc_rate' => null,
            'is_field_leader' => false,
            'description' => '',
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultRequirementForm(): array
    {
        return [
            'title' => '',
            'description' => '',
            'category' => 'general',
            'is_required' => true,
            'sort_order' => 10,
            'is_active' => true,
        ];
    }

    private function resetRankForm(): void
    {
        $this->rankForm = $this->defaultRankForm();
    }

    private function resetRequirementForm(): void
    {
        $this->editingRequirementId = null;
        $this->requirementForm = $this->defaultRequirementForm();
    }
}
