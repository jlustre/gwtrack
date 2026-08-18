<?php

namespace App\Services\Prospects;

use App\Models\ProspectFunnelStage;

class PipelineStageInfoService
{
    /**
     * @return array{
     *     title: string,
     *     slug: string,
     *     funnel_key: string,
     *     phase: string|null,
     *     summary: string,
     *     objectives: list<string>,
     *     recommended_actions: list<string>,
     *     advance_when: string,
     *     conversion_weight: float|null,
     *     is_terminal: bool,
     *     automation: list<string>
     * }
     */
    public function forStage(string $funnelKey, ProspectFunnelStage $stage): array
    {
        $slug = (string) $stage->slug;
        $configured = config("pipeline-stage-info.{$funnelKey}.{$slug}", []);
        $phase = $this->phaseLabel($funnelKey, $slug);

        return [
            'title' => (string) $stage->name,
            'slug' => $slug,
            'funnel_key' => $funnelKey,
            'phase' => $phase,
            'summary' => (string) ($configured['summary'] ?? $this->fallbackSummary($funnelKey, $stage, $phase)),
            'objectives' => $configured['objectives'] ?? $this->fallbackObjectives($funnelKey, $stage, $phase),
            'recommended_actions' => $configured['recommended_actions'] ?? $this->fallbackActions($funnelKey, $stage, $phase),
            'advance_when' => (string) ($configured['advance_when'] ?? $this->fallbackAdvanceWhen($funnelKey, $stage, $phase)),
            'conversion_weight' => $stage->conversion_weight !== null ? (float) $stage->conversion_weight : null,
            'is_terminal' => (bool) $stage->is_terminal,
            'automation' => $this->automationNotes($stage->auto_task_template),
        ];
    }

    private function phaseLabel(string $funnelKey, string $slug): ?string
    {
        foreach (config("crm-funnels.pipeline_summary_groups.{$funnelKey}", []) as $group) {
            if (in_array($slug, $group['slugs'] ?? [], true)) {
                return (string) ($group['label'] ?? null);
            }
        }

        return null;
    }

    private function fallbackSummary(string $funnelKey, ProspectFunnelStage $stage, ?string $phase): string
    {
        if ($stage->is_terminal) {
            return $funnelKey === 'recruiting'
                ? 'This is a final recruiting outcome stage. No further pipeline movement is expected unless the candidate re-enters the funnel.'
                : 'This is a final sales outcome stage. The opportunity is closed and should remain here unless the relationship re-opens.';
        }

        $phaseText = filled($phase) ? " It sits in the {$phase} phase of the funnel." : '';

        return "Track prospects in {$stage->name} and keep activity, follow-ups, and next steps current.{$phaseText}";
    }

    /**
     * @return list<string>
     */
    private function fallbackObjectives(string $funnelKey, ProspectFunnelStage $stage, ?string $phase): array
    {
        if ($stage->is_terminal) {
            return $funnelKey === 'recruiting'
                ? ['Document the final outcome', 'Archive notes for future reference', 'Celebrate wins or capture lessons learned']
                : ['Confirm the final outcome is accurate', 'Document why the case closed', 'Request referrals when appropriate'];
        }

        return [
            'Keep every prospect in this stage actively worked',
            'Log calls, meetings, and notes the same day they happen',
            filled($phase) ? "Move prospects forward when {$phase} goals are met" : 'Move prospects forward when the next step is clearly complete',
        ];
    }

    /**
     * @return list<string>
     */
    private function fallbackActions(string $funnelKey, ProspectFunnelStage $stage, ?string $phase): array
    {
        if ($stage->is_terminal) {
            return ['Review the prospect record for completeness', 'Set any final follow-up or nurture plan'];
        }

        return [
            'Review overdue follow-ups in this column',
            'Use Log Call or Log Activity on each card before moving stages',
            $funnelKey === 'recruiting'
                ? 'Confirm the candidate knows the next recruiting milestone'
                : 'Confirm needs, timeline, and next appointment before advancing',
        ];
    }

    private function fallbackAdvanceWhen(string $funnelKey, ProspectFunnelStage $stage, ?string $phase): string
    {
        if ($stage->is_terminal) {
            return 'Do not advance unless the prospect or candidate re-enters an active funnel stage.';
        }

        return $funnelKey === 'recruiting'
            ? 'Move the card when the recruiting milestone for this stage is complete and the next step is scheduled or confirmed.'
            : 'Move the card when the sales milestone for this stage is complete and the client is ready for the next step in the process.';
    }

    /**
     * @param  array<string, mixed>|null  $template
     * @return list<string>
     */
    private function automationNotes(?array $template): array
    {
        if ($template === null || $template === []) {
            return [];
        }

        $notes = [];
        $followUp = $template['follow_up'] ?? null;

        if (is_array($followUp)) {
            if (filled($followUp['task_title'] ?? null)) {
                $notes[] = 'Creates task: '.$followUp['task_title'];
            }

            if (filled($followUp['followup_type'] ?? null)) {
                $notes[] = 'Creates follow-up type: '.str($followUp['followup_type'])->headline()->toString();
            }

            if (isset($followUp['offset_days'])) {
                $notes[] = 'Follow-up timing: '.(int) $followUp['offset_days'].' day(s) after stage entry';
            }

            if (filled($followUp['notes'] ?? null)) {
                $notes[] = (string) $followUp['notes'];
            }
        }

        return $notes;
    }
}
