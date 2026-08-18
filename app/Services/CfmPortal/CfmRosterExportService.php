<?php

namespace App\Services\CfmPortal;

use App\Models\CfmPromotion;
use App\Models\CfmRiskScore;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CfmRosterExportService
{
    public function __construct(
        private readonly CfmPortalDashboardService $dashboard,
        private readonly CfmRiskAssessmentService $risk,
        private readonly CfmPromotionReadinessService $promotion,
    ) {}

    public function downloadCsv(User $cfm): StreamedResponse
    {
        $rows = $this->buildRows($cfm);
        $filename = 'cfm-roster-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Name',
                'Email',
                'Rank',
                'Status',
                'Onboarding %',
                'FAP %',
                'Licensing %',
                'Risk Score',
                'Risk Level',
                'Promotion Readiness %',
                'Promotion Status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['email'],
                    $row['rank'],
                    $row['status_label'],
                    $row['onboarding_percent'],
                    $row['fap_percent'],
                    $row['licensing_percent'],
                    $row['risk_score'],
                    $row['risk_level'],
                    $row['promotion_readiness'],
                    $row['promotion_status'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildRows(User $cfm): array
    {
        $trainees = $this->dashboard->traineesFor($cfm);

        return $trainees->map(function (array $row) use ($cfm): array {
            $trainee = User::query()->with('rank')->find($row['id']);

            if (! $trainee) {
                return [];
            }

            $risk = CfmRiskScore::query()
                ->where('cfm_id', $cfm->id)
                ->where('trainee_id', $trainee->id)
                ->latest('assessed_at')
                ->first();

            if (! $risk || $risk->assessed_at?->lt(now()->subDay())) {
                $assessment = $this->risk->assessAndStore($cfm, $trainee, $row);
            } else {
                $assessment = [
                    'score' => $risk->score,
                    'level' => $risk->level,
                ];
            }

            $promotion = CfmPromotion::query()
                ->where('cfm_id', $cfm->id)
                ->where('trainee_id', $trainee->id)
                ->first();

            if (! $promotion || $promotion->updated_at?->lt(now()->subDay())) {
                $promotion = $this->promotion->syncForTrainee($cfm, $trainee);
            }

            return [
                'name' => $row['name'],
                'email' => $row['email'],
                'rank' => $row['rank_name'],
                'status_label' => $row['status_label'],
                'onboarding_percent' => $row['onboarding_percent'],
                'fap_percent' => $row['fap_percent'],
                'licensing_percent' => $row['licensing_percent'],
                'risk_score' => $assessment['score'],
                'risk_level' => $assessment['level'],
                'promotion_readiness' => $promotion->readiness_percent,
                'promotion_status' => $promotion->status,
            ];
        })->filter()->values()->all();
    }
}
