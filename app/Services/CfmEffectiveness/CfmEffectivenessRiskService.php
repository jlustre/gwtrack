<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessRisk;
use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\User;
use Illuminate\Support\Collection;

class CfmEffectivenessRiskService
{
    public function __construct(
        private readonly CfmEffectivenessMetricsService $metrics,
        private readonly CfmEffectivenessFeedbackService $feedback,
    ) {}

    /**
     * @return Collection<int, CfmEffectivenessRisk>
     */
    public function detectAndStore(User $cfm): Collection
    {
        $metrics = $this->metrics->calculateFor($cfm);
        $feedback = $this->feedback->aggregatedFeedbackFor($cfm);
        $thresholds = config('cfm-effectiveness.risk_thresholds', []);
        $detected = collect();

        if (($metrics['retention_rate']['score'] ?? 100) < ($thresholds['retention_rate'] ?? 60)) {
            $detected->push($this->flag($cfm, 'low_retention', 'high', 'Trainee retention is below agency threshold.'));
        }

        if (($metrics['fap_completion_rate']['score'] ?? 100) < ($thresholds['fap_completion_rate'] ?? 50)) {
            $detected->push($this->flag($cfm, 'low_fap_completion', 'high', 'FAP completion rate needs immediate coaching attention.'));
        }

        if (($metrics['licensing_completion_rate']['score'] ?? 100) < ($thresholds['licensing_completion_rate'] ?? 40)) {
            $detected->push($this->flag($cfm, 'low_licensing_success', 'medium', 'Licensing completion is below expected levels.'));
        }

        $responseHours = $metrics['responsiveness_score']['meta']['average_hours'] ?? 0;
        if ($responseHours > ($thresholds['response_hours'] ?? 72)) {
            $detected->push($this->flag($cfm, 'slow_response', 'medium', 'Average mentor response time exceeds 72 hours.'));
        }

        $satisfaction = $feedback['overall_average'] ?? null;
        if ($satisfaction !== null && $satisfaction < ($thresholds['trainee_satisfaction'] ?? 3.0)) {
            $detected->push($this->flag($cfm, 'low_satisfaction', 'high', 'Trainee satisfaction scores indicate mentorship gaps.'));
        }

        $lastActivity = $cfm->cfmMentorProfile?->last_mentor_activity_at;
        if ($lastActivity && $lastActivity->diffInDays(now()) >= ($thresholds['inactive_days'] ?? 14)) {
            $detected->push($this->flag($cfm, 'inactive_cfm', 'high', 'No recent mentor activity detected in the last 14 days.'));
        }

        return $detected;
    }

    /**
     * @return Collection<int, CfmEffectivenessRisk>
     */
    public function openRisksFor(User $cfm): Collection
    {
        return CfmEffectivenessRisk::query()
            ->where('cfm_id', $cfm->id)
            ->whereNull('resolved_at')
            ->latest('detected_at')
            ->get();
    }

    private function flag(User $cfm, string $type, string $severity, string $message): CfmEffectivenessRisk
    {
        $existing = CfmEffectivenessRisk::query()
            ->where('cfm_id', $cfm->id)
            ->where('risk_type', $type)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return CfmEffectivenessRisk::query()->create([
            'cfm_id' => $cfm->id,
            'risk_type' => $type,
            'severity' => $severity,
            'message' => $message,
            'detected_at' => now(),
        ]);
    }
}
