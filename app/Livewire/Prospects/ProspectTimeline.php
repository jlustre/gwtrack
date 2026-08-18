<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectTimeline extends Component
{
    public Prospect $prospect;

    public string $filter = 'all';

    /** @var list<array<string, mixed>> */
    public array $timeline = [];

    /** @var list<string> */
    public array $filters = [
        'all',
        'activity',
        'communication',
        'note',
        'stage',
        'calendar',
        'appointment',
        'followup',
        'task',
        'fna',
        'conversion',
        'file',
    ];

    public function mount(Prospect $prospect, ProspectFunnelService $funnels): void
    {
        $this->prospect = $prospect;
        $this->authorize('view', $prospect);
        $this->loadTimeline($funnels);
    }

    public function updatedFilter(ProspectFunnelService $funnels): void
    {
        $this->loadTimeline($funnels);
    }

    #[On('prospect-timeline-refresh')]
    public function refreshTimeline(ProspectFunnelService $funnels): void
    {
        $this->loadTimeline($funnels);
    }

    private function loadTimeline(ProspectFunnelService $funnels): void
    {
        $items = $funnels->timelineFor($this->prospect->fresh());

        if (! in_array($this->filter, $this->filters, true)) {
            $this->filter = 'all';
        }

        if ($this->filter !== 'all') {
            $items = array_values(array_filter($items, fn (array $item): bool => $item['type'] === $this->filter));
        }

        $this->timeline = $items;
    }

    public function render(): View
    {
        return view('livewire.prospects.prospect-timeline');
    }
}
