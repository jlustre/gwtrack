<?php

namespace App\Livewire\Admin\Training;

use App\Models\TrainingPath;
use App\Services\Training\TrainingAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminPathEditor extends Component
{
    public TrainingPath $path;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $audience = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    /** @var list<array{module_id: int|null, sort_order: int, is_required: bool}> */
    public array $moduleRows = [];

    public ?int $attachModuleId = null;

    public function mount(TrainingPath $path): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        $this->path = $path->load(['modules']);
        $this->fillFromPath();
    }

    public function savePath(TrainingAdminService $admin): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audience' => ['nullable', 'string', 'max:100'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ]);

        $this->path = $admin->updatePath($this->path, [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'audience' => $this->audience,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ]);

        $admin->syncPathModules($this->path, $this->moduleRows);
        $this->path->refresh()->load('modules');
        $this->fillModuleRows();

        session()->flash('admin_training_status', 'path-saved');
    }

    public function addModuleRow(): void
    {
        if (! $this->attachModuleId) {
            return;
        }

        if (collect($this->moduleRows)->contains(fn (array $row): bool => (int) $row['module_id'] === (int) $this->attachModuleId)) {
            return;
        }

        $this->moduleRows[] = [
            'module_id' => $this->attachModuleId,
            'sort_order' => (count($this->moduleRows) + 1) * 10,
            'is_required' => true,
        ];

        $this->attachModuleId = null;
    }

    public function removeModuleRow(int $index): void
    {
        unset($this->moduleRows[$index]);
        $this->moduleRows = array_values($this->moduleRows);
    }

    public function render(TrainingAdminService $admin): View
    {
        $attachedIds = collect($this->moduleRows)->pluck('module_id')->filter()->all();

        return view('livewire.admin.training.admin-path-editor', [
            'availableModules' => $admin->publishedModulesForPathBuilder()->reject(
                fn ($module) => in_array($module->id, $attachedIds, true)
            ),
            'moduleLookup' => $admin->publishedModulesForPathBuilder()->keyBy('id'),
            'audiences' => [
                'associate' => 'Associate',
                'mentor' => 'Mentor',
                'leader' => 'Leader',
            ],
        ]);
    }

    private function fillFromPath(): void
    {
        $this->name = $this->path->name;
        $this->code = $this->path->code;
        $this->description = $this->path->description ?? '';
        $this->audience = $this->path->audience ?? '';
        $this->sortOrder = (int) $this->path->sort_order;
        $this->isActive = (bool) $this->path->is_active;
        $this->fillModuleRows();
    }

    private function fillModuleRows(): void
    {
        $this->moduleRows = $this->path->modules->map(fn ($module): array => [
            'module_id' => $module->id,
            'sort_order' => (int) $module->pivot->sort_order,
            'is_required' => (bool) $module->pivot->is_required,
        ])->values()->all();
    }
}
