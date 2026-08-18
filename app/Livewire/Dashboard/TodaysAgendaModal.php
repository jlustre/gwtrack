<?php

namespace App\Livewire\Dashboard;

use App\Services\TodaysAgendaService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TodaysAgendaModal extends Component
{
    public bool $show = false;

    /** @var array<string, mixed> */
    public array $agenda = [];

    /** @var array<string, bool> */
    public array $collapsedSections = [];

    #[On('open-todays-agenda-modal')]
    public function open(): void
    {
        $this->agenda = app(TodaysAgendaService::class)->forUser(auth()->user());
        $this->collapsedSections = collect($this->agenda['sections'] ?? [])
            ->mapWithKeys(fn (array $section): array => [
                (string) $section['key'] => ($section['count'] ?? 0) === 0,
            ])
            ->all();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function toggleSection(string $key): void
    {
        $this->collapsedSections[$key] = ! ($this->collapsedSections[$key] ?? false);
    }

    public function expandAllSections(): void
    {
        $this->collapsedSections = collect($this->collapsedSections)
            ->map(fn (): bool => false)
            ->all();
    }

    public function collapseAllSections(): void
    {
        $this->collapsedSections = collect($this->collapsedSections)
            ->map(fn (): bool => true)
            ->all();
    }

    public function render(): View
    {
        return view('livewire.dashboard.todays-agenda-modal');
    }
}
