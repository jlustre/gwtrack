<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class LogActivityModal extends Component
{
    public bool $show = false;

    public ?string $prospectId = null;

    public ?string $initialActivityType = null;

    public int $formKey = 0;

    #[On('open-log-activity-modal')]
    public function open(string $prospectId, ?string $activityType = null): void
    {
        $prospect = Prospect::query()->findOrFail($prospectId);
        $this->authorize('update', $prospect);

        $this->prospectId = $prospectId;
        $this->initialActivityType = $activityType;
        $this->formKey++;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->prospectId = null;
        $this->initialActivityType = null;
    }

    #[On('prospect-activity-logged')]
    public function onActivityLogged(): void
    {
        if ($this->show) {
            $this->close();
        }
    }

    #[On('cancel-prospect-activity-log-form')]
    public function onCancel(): void
    {
        if ($this->show) {
            $this->close();
        }
    }

    public function render(): View
    {
        return view('livewire.prospects.log-activity-modal');
    }
}
