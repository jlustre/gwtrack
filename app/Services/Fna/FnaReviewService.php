<?php

namespace App\Services\Fna;

use App\Events\Fna\FnaApproved;
use App\Events\Fna\FnaRevisionRequested;
use App\Events\Fna\FnaSubmittedForReview;
use App\Models\FnaRecord;
use App\Models\FnaReviewComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FnaReviewService
{
    public function __construct(
        private FnaWorkflowService $workflow,
        private FnaRecordService $records,
        private FnaCompletenessService $completeness,
    ) {}

    public function submitForReview(FnaRecord $fna, User $associate): FnaRecord
    {
        if ((int) $fna->owner_user_id !== $associate->id) {
            throw new InvalidArgumentException('Only the FNA owner can submit for review.');
        }

        if (! $this->completeness->meetsThreshold($fna)) {
            throw new InvalidArgumentException('FNA does not meet the minimum completeness threshold.');
        }

        $cfm = $this->records->resolveCfmForOwner($fna->owner);

        if (! $cfm) {
            throw new InvalidArgumentException('No active CFM mentor assignment found. Contact your mentor before submitting.');
        }

        return DB::transaction(function () use ($fna, $associate, $cfm): FnaRecord {
            if (in_array($fna->status, ['draft', 'revision_requested'], true)) {
                $fna = $this->workflow->transition($fna, $associate, 'ready_for_review');
            }

            if ($fna->status === 'ready_for_review') {
                $fna = $this->workflow->transition($fna, $associate, 'submitted_to_cfm');
            }

            if ($fna->status !== 'submitted_to_cfm') {
                throw new InvalidArgumentException('FNA cannot be submitted from its current status.');
            }

            $fna->update(['cfm_user_id' => $cfm->id]);

            $this->records->logActivity($fna, $associate, 'submitted', 'FNA submitted to CFM for review.');

            event(new FnaSubmittedForReview($fna->fresh(), $associate, $cfm));

            return $fna->fresh();
        });
    }

    public function beginReview(FnaRecord $fna, User $cfm): FnaRecord
    {
        if ($fna->status !== 'submitted_to_cfm') {
            return $fna;
        }

        return $this->workflow->transition($fna, $cfm, 'under_cfm_review');
    }

    public function approve(FnaRecord $fna, User $cfm, ?string $comment = null, bool $isInternal = false): FnaRecord
    {
        return DB::transaction(function () use ($fna, $cfm, $comment, $isInternal): FnaRecord {
            if (! in_array($fna->status, ['submitted_to_cfm', 'under_cfm_review'], true)) {
                throw new InvalidArgumentException('FNA is not awaiting CFM review.');
            }

            if ($fna->status === 'submitted_to_cfm') {
                $fna = $this->beginReview($fna, $cfm);
            }

            if ($comment) {
                $this->addComment($fna, $cfm, $comment, 'approval', $isInternal);
                $fna->update(['cfm_feedback_summary' => $comment]);
            }

            $fna = $this->workflow->transition($fna, $cfm, 'approved_by_cfm');

            $this->records->logActivity($fna, $cfm, 'approved', 'FNA approved by CFM.', ['comment' => $comment]);

            event(new FnaApproved($fna, $cfm, $comment));

            return $fna->fresh();
        });
    }

    public function requestRevision(FnaRecord $fna, User $cfm, string $comment): FnaRecord
    {
        $comment = trim($comment);

        if ($comment === '') {
            throw new InvalidArgumentException('A revision comment is required.');
        }

        return DB::transaction(function () use ($fna, $cfm, $comment): FnaRecord {
            if (! in_array($fna->status, ['submitted_to_cfm', 'under_cfm_review'], true)) {
                throw new InvalidArgumentException('FNA is not awaiting CFM review.');
            }

            if ($fna->status === 'submitted_to_cfm') {
                $fna = $this->beginReview($fna, $cfm);
            }

            $this->addComment($fna, $cfm, $comment, 'revision', false);
            $fna->update(['cfm_feedback_summary' => $comment]);

            $fna = $this->workflow->transition($fna, $cfm, 'revision_requested');

            $this->records->logActivity($fna, $cfm, 'revision_requested', 'CFM requested FNA revisions.', ['comment' => $comment]);

            event(new FnaRevisionRequested($fna, $cfm, $comment));

            return $fna->fresh();
        });
    }

    protected function addComment(
        FnaRecord $fna,
        User $user,
        string $body,
        string $type,
        bool $isInternal,
    ): FnaReviewComment {
        return $fna->reviewComments()->create([
            'user_id' => $user->id,
            'comment_type' => $type,
            'body' => $body,
            'is_internal' => $isInternal,
        ]);
    }
}
