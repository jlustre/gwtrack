<?php

namespace App\Livewire\Prospects;

use App\Models\FnaRecord;
use App\Models\Prospect;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectFnaPanel extends Component
{
    public Prospect $prospect;

    public function mount(Prospect $prospect): void
    {
        $this->prospect = $prospect;
        $this->authorize('view', $prospect);
    }

    public function render(): View
    {
        $records = FnaRecord::query()
            ->where('prospect_id', $this->prospect->id)
            ->with(['owner:id,name', 'cfm:id,name', 'calendarEvent'])
            ->latest('updated_at')
            ->get();

        return view('livewire.prospects.prospect-fna-panel', [
            'records' => $records,
            'fnaStatuses' => config('prospects.fna_statuses', []),
            'canCreateFna' => auth()->user()->can('create', FnaRecord::class),
        ]);
    }
}
