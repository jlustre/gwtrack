<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessReport;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CfmEffectivenessReports extends Component
{
    #[Url]
    public ?int $cfmId = null;

    public string $reportType = 'effectiveness_summary';

    public string $periodType = 'quarterly';

    public string $audience = 'cfm';

    public function mount(): void
    {
        abort_unless(
            auth()->user()->can('view CFM effectiveness') || auth()->user()->can('view CFM reports'),
            403,
        );
    }

    public function generateReport(CfmEffectivenessReportService $reports): void
    {
        $viewer = auth()->user();
        $cfm = $this->resolveCfm($viewer, $reports);

        $report = $reports->generate($viewer, $cfm, $this->reportType, $this->periodType, $this->audience);

        session()->flash('cfm_effectiveness_status', 'Report generated successfully.');

        $this->redirect(route('cfm.effectiveness.reports.download', $report), navigate: true);
    }

    public function render(CfmEffectivenessReportService $reports): View
    {
        $viewer = auth()->user();
        $cfm = $this->resolveCfm($viewer, $reports);

        return view('livewire.cfm-effectiveness.reports', [
            'center' => $reports->centerFor($viewer, $cfm),
            'preview' => $reports->buildPayload($viewer, $cfm, $this->reportType, $this->periodType),
            'cfmOptions' => $this->cfmOptions($viewer),
        ]);
    }

    private function resolveCfm(User $viewer, CfmEffectivenessReportService $reports): User
    {
        return $reports->resolveCfm($viewer, $this->cfmId);
    }

    /**
     * @return array<int, string>
     */
    private function cfmOptions(User $viewer): array
    {
        if (! $viewer->can('view CFM reports')) {
            return [];
        }

        return User::role('certified-field-mentor')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $cfm) => [$cfm->id => $cfm->name])
            ->all();
    }
}
