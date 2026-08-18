<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Prospects\ProspectFunnelService;

class FnaProspectBridge
{
    public function __construct(
        private ProspectFunnelService $funnels,
    ) {}

    public function syncProspectFnaStatus(FnaRecord $fna): void
    {
        if (! $fna->prospect_id) {
            return;
        }

        $prospect = Prospect::query()->find($fna->prospect_id);

        if (! $prospect) {
            return;
        }

        $mapped = config('fna.prospect_fna_status_map')[$fna->status] ?? 'not_started';

        if ($prospect->fna_status !== $mapped) {
            $prospect->update([
                'fna_status' => $mapped,
                'last_activity_at' => now(),
            ]);
        }
    }

    public function logProspectTimeline(FnaRecord $fna, User $actor, string $summary, string $activityType = 'financial_review'): void
    {
        if (! $fna->prospect_id) {
            return;
        }

        $prospect = Prospect::query()->find($fna->prospect_id);

        if (! $prospect) {
            return;
        }

        $this->funnels->logActivity($prospect, $actor, [
            'activity_type' => $activityType,
            'occurred_at' => now(),
            'notes' => $summary,
            'outcome' => "FNA {$fna->reference_code}",
        ]);
    }

    public function advanceStageAfterApproval(FnaRecord $fna, User $actor): void
    {
        if (! $fna->prospect_id) {
            return;
        }

        $prospect = Prospect::query()->with('stage')->find($fna->prospect_id);

        if (! $prospect) {
            return;
        }

        $targetStage = PipelineStage::query()
            ->whereNull('user_id')
            ->where('slug', 'financial-review')
            ->where('is_active', true)
            ->first();

        if (! $targetStage || (int) $prospect->pipeline_stage_id === (int) $targetStage->id) {
            return;
        }

        $this->funnels->moveStage($prospect, $actor, $targetStage->id, 'fna_approval');
    }

    public function linkProspect(FnaRecord $fna, Prospect $prospect, User $actor): FnaRecord
    {
        if ((int) $prospect->owner_id !== (int) $fna->owner_user_id) {
            throw new \InvalidArgumentException('Prospect must belong to the FNA owner.');
        }

        $fna->update(['prospect_id' => $prospect->id]);
        $this->syncProspectFnaStatus($fna->fresh());
        $this->logProspectTimeline($fna->fresh(), $actor, "FNA {$fna->reference_code} linked to prospect profile.");

        return $fna->fresh();
    }
}
