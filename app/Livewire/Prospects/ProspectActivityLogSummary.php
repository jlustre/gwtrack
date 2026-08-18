<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectActivityLogSummaryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProspectActivityLogSummary extends Component
{
    public string $grouping = 'monthly';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);

        $this->startDate = now()->subDays(29)->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function applyPreset(string $preset): void
    {
        match ($preset) {
            'today' => $this->setRange(now()->toDateString(), now()->toDateString(), 'daily'),
            'week' => $this->setRange(
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
                'daily',
            ),
            'month' => $this->setRange(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                'weekly',
            ),
            'last_30' => $this->setRange(
                now()->subDays(29)->toDateString(),
                now()->toDateString(),
                'daily',
            ),
            default => null,
        };
    }

    public function updatedStartDate(): void
    {
        $this->normalizeRange();
    }

    public function updatedEndDate(): void
    {
        $this->normalizeRange();
    }

    public function render(ProspectActivityLogSummaryService $summaryService): View
    {
        $validated = $this->validate([
            'grouping' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $start = Carbon::parse($validated['startDate'])->startOfDay();
        $end = Carbon::parse($validated['endDate'])->endOfDay();

        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366)->startOfDay();
            $this->startDate = $start->toDateString();
        }

        $summary = $summaryService->summarize(
            auth()->user(),
            $start,
            $end,
            $validated['grouping'],
        );

        return view('livewire.prospects.prospect-activity-log-summary', [
            'summary' => $summary,
            'metricDefinitions' => config('prospects.activity_log_summary_metrics', []),
            'rangeLabel' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
        ]);
    }

    private function setRange(string $start, string $end, string $grouping): void
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->grouping = $grouping;
    }

    private function normalizeRange(): void
    {
        if ($this->startDate === '' || $this->endDate === '') {
            return;
        }

        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        if ($start->gt($end)) {
            $this->endDate = $this->startDate;
        }
    }
}
