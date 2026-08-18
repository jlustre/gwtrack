<?php

namespace App\Livewire\Team;

use App\Services\CfmPortal\CfmPortalDashboardService;
use App\Services\CfmPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('My Trainees')]
class MyTrainees extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'filter', except: 'all')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort', except: 'priority')]
    public string $sortBy = 'priority';

    #[Url(as: 'view', except: 'table')]
    public string $viewMode = 'table';

    public function setFilter(string $filter): void
    {
        $this->statusFilter = $filter;
    }

    public function setSort(string $sort): void
    {
        $allowed = ['priority', 'name', 'fap', 'licensing', 'onboarding', 'overall', 'activity', 'joined'];

        if (in_array($sort, $allowed, true)) {
            $this->sortBy = $sort;
        }
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['table', 'cards'], true) ? $mode : 'table';
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->search = '';
    }

    public function render(CfmPortalService $portalService, CfmPortalDashboardService $dashboard): View
    {
        $viewer = auth()->user();
        abort_unless($viewer && $viewer->canAccessCfmPortal(), 403);

        $portal = $portalService->payloadFor($viewer);
        $cfm = $portal['cfmUser'];
        $trainees = $dashboard->traineesFor($cfm);
        $summary = $dashboard->summaryFor($cfm);
        $pendingAssignments = $dashboard->pendingAssignmentsFor($cfm);
        $suggestions = $dashboard->aiSuggestionsFor($cfm, $trainees);
        $filtered = $this->filteredAndSorted($trainees);

        $filterCounts = [
            'all' => $trainees->count(),
            'active' => $trainees->where('is_active', true)->count(),
            'new' => $trainees->where('is_new', true)->count(),
            'at_risk' => $trainees->where('is_at_risk', true)->count(),
            'licensing' => $trainees->where('status', 'licensing')->count(),
            'fap' => $trainees->where('status', 'fap')->count(),
            'promotion_ready' => $trainees->where('is_promotion_ready', true)->count(),
            'inactive' => $trainees->where('is_active', false)->count(),
            'needs_contact' => $trainees->where('needs_first_contact', true)->count(),
        ];

        return view('livewire.team.my-trainees', [
            'viewer' => $viewer,
            'cfm' => $cfm,
            'summary' => $summary,
            'trainees' => $filtered,
            'totalTrainees' => $trainees->count(),
            'pendingAssignments' => $pendingAssignments,
            'suggestions' => $suggestions,
            'filterCounts' => $filterCounts,
            'todayLabel' => now()->format('l, F j, Y'),
            'portalUrl' => route('cfm.portal'),
            'rosterExportUrl' => route('cfm.portal.roster.export'),
        ])->layout('layouts.app');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $trainees
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredAndSorted(Collection $trainees): Collection
    {
        $needle = strtolower(trim($this->search));

        $filtered = $trainees
            ->filter(function (array $trainee) use ($needle): bool {
                if ($needle !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $trainee['name'] ?? '',
                        $trainee['email'] ?? '',
                        $trainee['phone'] ?? '',
                        $trainee['rank'] ?? '',
                        $trainee['location'] ?? '',
                        $trainee['sponsor_name'] ?? '',
                    ])));

                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                return match ($this->statusFilter) {
                    'active' => $trainee['is_active'] && ! $trainee['is_at_risk'],
                    'new' => $trainee['is_new'],
                    'at_risk' => $trainee['is_at_risk'],
                    'licensing' => $trainee['status'] === 'licensing',
                    'fap' => $trainee['status'] === 'fap',
                    'promotion_ready' => $trainee['is_promotion_ready'],
                    'inactive' => ! $trainee['is_active'],
                    'needs_contact' => (bool) $trainee['needs_first_contact'],
                    default => true,
                };
            })
            ->values();

        $priorityRank = [
            'at_risk' => 0,
            'new' => 1,
            'licensing' => 2,
            'fap' => 3,
            'active' => 4,
            'promotion_ready' => 5,
            'inactive' => 6,
        ];

        return $filtered->sort(function (array $a, array $b) use ($priorityRank): int {
            return match ($this->sortBy) {
                'name' => strcasecmp($a['name'], $b['name']),
                'fap' => ($b['fap_percent'] <=> $a['fap_percent']) ?: strcasecmp($a['name'], $b['name']),
                'licensing' => ($b['licensing_percent'] <=> $a['licensing_percent']) ?: strcasecmp($a['name'], $b['name']),
                'onboarding' => ($b['onboarding_percent'] <=> $a['onboarding_percent']) ?: strcasecmp($a['name'], $b['name']),
                'overall' => ($b['overall_percent'] <=> $a['overall_percent']) ?: strcasecmp($a['name'], $b['name']),
                'activity' => ($a['days_idle'] <=> $b['days_idle']) ?: strcasecmp($a['name'], $b['name']),
                'joined' => strcmp((string) ($b['joined_raw'] ?? ''), (string) ($a['joined_raw'] ?? '')) ?: strcasecmp($a['name'], $b['name']),
                default => (($priorityRank[$a['status']] ?? 99) <=> ($priorityRank[$b['status']] ?? 99))
                    ?: (($b['overdue_tasks'] <=> $a['overdue_tasks']))
                    ?: strcasecmp($a['name'], $b['name']),
            };
        })->values();
    }
}
