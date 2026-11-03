<?php

namespace App\Livewire\Reports;

use App\Services\LeadershipReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LeadershipReports extends Component
{
    public string $period = 'monthly';

    public string $teamId = '';

    public string $rankId = '';

    public string $role = '';

    public string $status = '';

    public int $page = 1;

    public function render(LeadershipReportService $reports): View
    {
        return view('livewire.reports.leadership-reports', [
            'report' => $reports->build(
                auth()->user(),
                $this->period,
                filters: [
                    'team_id' => $this->teamId,
                    'rank_id' => $this->rankId,
                    'role' => $this->role,
                    'status' => $this->status,
                ],
                page: $this->page,
            ),
        ]);
    }

    public function updatedPeriod(): void
    {
        $this->page = 1;
    }

    public function updatedTeamId(): void
    {
        $this->page = 1;
    }

    public function updatedRankId(): void
    {
        $this->page = 1;
    }

    public function updatedRole(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function goToPreviousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function goToNextPage(LeadershipReportService $reports): void
    {
        $filters = [
            'team_id' => $this->teamId,
            'rank_id' => $this->rankId,
            'role' => $this->role,
            'status' => $this->status,
        ];
        $report = $reports->build(auth()->user(), $this->period, filters: $filters, page: $this->page);

        if ($report['pagination']['has_more']) {
            $this->page++;
        }
    }
}
