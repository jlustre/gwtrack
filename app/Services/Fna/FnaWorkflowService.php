<?php

namespace App\Services\Fna;

use App\Exceptions\Fna\InvalidFnaStatusTransitionException;
use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FnaWorkflowService
{
    public function __construct(
        private FnaRecordService $records,
    ) {}

    public function canTransition(FnaRecord $fna, string $toStatus): bool
    {
        $allowed = config('fna.transitions')[$fna->status] ?? [];

        return in_array($toStatus, $allowed, true);
    }

    public function transition(FnaRecord $fna, User $user, string $toStatus, array $metadata = [], string $source = 'manual'): FnaRecord
    {
        if (! $this->canTransition($fna, $toStatus)) {
            throw InvalidFnaStatusTransitionException::fromTo($fna->status, $toStatus);
        }

        return DB::transaction(function () use ($fna, $user, $toStatus, $metadata, $source): FnaRecord {
            $fromStatus = $fna->status;

            $updates = ['status' => $toStatus];

            if ($toStatus === 'submitted_to_cfm') {
                $cfm = $this->records->resolveCfmForOwner($fna->owner);
                $updates['cfm_user_id'] = $cfm?->id;
                $updates['submitted_at'] = now();
            }

            if ($toStatus === 'approved_by_cfm') {
                $updates['approved_at'] = now();
            }

            if ($toStatus === 'presented_to_prospect') {
                $updates['presented_at'] = now();
            }

            if (in_array($toStatus, ['closed', 'archived'], true)) {
                $updates['closed_at'] = now();
            }

            if ($toStatus === 'revision_requested') {
                $updates['submitted_at'] = null;
            }

            $fna->update($updates);

            $fna->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_user_id' => $user->id,
                'change_source' => $source,
                'metadata' => $metadata ?: null,
            ]);

            $this->records->logActivity(
                $fna,
                $user,
                'status_changed',
                "Status changed from {$fromStatus} to {$toStatus}.",
                ['from' => $fromStatus, 'to' => $toStatus] + $metadata
            );

            return $fna->fresh();
        });
    }
}
