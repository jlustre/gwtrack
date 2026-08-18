<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\FnaExportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaExportPreview extends Component
{
    public FnaRecord $fna;

    /** @var array<string, mixed> */
    public array $exportData = [];

    public function mount(FnaRecord $fna, FnaExportService $export): void
    {
        $this->authorize('export', $fna);

        $this->fna = $fna;
        $this->exportData = $export->buildExportData($fna, auth()->user());
    }

    public function render(): View
    {
        return view('livewire.fna.fna-export-preview');
    }
}
