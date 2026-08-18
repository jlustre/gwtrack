<?php

namespace App\Services\Prospects;

use App\Events\Prospects\ProspectConverted;
use App\Models\Prospect;
use App\Models\ProspectConversion;
use App\Models\RegistrationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProspectConversionService
{
    public function __construct(
        private readonly ProspectFunnelService $funnelService,
        private readonly ProspectShareService $shareService,
    ) {}

    /**
     * @return array{invitation: RegistrationInvitation, invitation_url: string, conversion: ProspectConversion}
     */
    public function convertToAssociate(Prospect $prospect, User $actor, ?string $notes = null): array
    {
        return DB::transaction(function () use ($prospect, $actor, $notes): array {
            $prospect = $prospect->fresh(['funnel']);

            $invitation = RegistrationInvitation::create([
                'sponsor_id' => $actor->id,
                'prospect_id' => $prospect->id,
                'code' => RegistrationInvitation::generateCode(),
                'email' => $prospect->email,
                'role_name' => 'member',
                'max_uses' => 1,
                'uses_count' => 0,
                'expires_at' => now()->addDays(14),
            ]);

            if ($this->isRecruitingFunnel($prospect)) {
                $stageId = $this->stageIdForFunnel($prospect->prospect_funnel_id, 'registration-link-sent');

                if ($stageId) {
                    $this->funnelService->moveStage($prospect, $actor, $stageId, 'conversion');
                }
            } else {
                $prospect->update(['converted_to' => 'pending']);
            }

            $conversion = $this->upsertAssociateConversion($prospect, $actor, $notes);

            $this->shareService->logAccess($prospect, $actor, 'conversion_associate_initiated', [
                'invitation_id' => $invitation->id,
                'conversion_id' => $conversion->id,
            ]);

            event(new ProspectConverted($prospect->fresh(), $actor, $conversion, 'initiated', $invitation));

            return [
                'invitation' => $invitation,
                'invitation_url' => $invitation->invitationUrl(),
                'conversion' => $conversion,
            ];
        });
    }

    public function completeAssociateConversion(RegistrationInvitation $invitation, User $newMember): void
    {
        if (! $invitation->prospect_id) {
            return;
        }

        DB::transaction(function () use ($invitation, $newMember): void {
            $prospect = Prospect::query()->findOrFail($invitation->prospect_id);
            $owner = $prospect->owner;

            if (! $owner) {
                return;
            }

            $conversion = ProspectConversion::query()
                ->where('prospect_id', $prospect->id)
                ->where('conversion_type', 'associate')
                ->whereNull('created_user_id')
                ->latest('id')
                ->first();

            if ($conversion) {
                $conversion->update([
                    'created_user_id' => $newMember->id,
                    'converted_at' => now(),
                ]);
            } else {
                $conversion = ProspectConversion::create([
                    'prospect_id' => $prospect->id,
                    'converted_by' => $owner->id,
                    'created_user_id' => $newMember->id,
                    'conversion_type' => 'associate',
                    'converted_at' => now(),
                    'notes' => 'Completed via registration invitation.',
                ]);
            }

            $prospect->update([
                'converted_to' => 'associate',
                'conversion_at' => now(),
                'status' => 'active',
            ]);

            $stageSlug = $this->isRecruitingFunnel($prospect) ? 'became-associate' : 'registered';
            $stageId = $this->stageIdForFunnel($prospect->prospect_funnel_id, $stageSlug)
                ?? $this->stageIdForFunnel($prospect->prospect_funnel_id, 'became-associate');

            if ($stageId) {
                $this->funnelService->moveStage($prospect, $owner, $stageId, 'conversion');
            }

            $this->funnelService->logActivity($prospect, $owner, [
                'activity_type' => 'recruitment_meeting',
                'outcome' => 'registered',
                'notes' => "{$newMember->name} completed registration and became an associate.",
            ]);

            $this->shareService->logAccess($prospect, $owner, 'conversion_associate_completed', [
                'conversion_id' => $conversion->id,
                'created_user_id' => $newMember->id,
            ]);

            event(new ProspectConverted($prospect->fresh(), $owner, $conversion->fresh(), 'completed'));
        });
    }

    public function convertToClient(
        Prospect $prospect,
        User $actor,
        string $policyReference,
        ?string $applicationReference = null,
        ?string $notes = null,
    ): ProspectConversion {
        return DB::transaction(function () use ($prospect, $actor, $policyReference, $applicationReference, $notes): ProspectConversion {
            $prospect->update([
                'is_client' => true,
                'converted_to' => 'client',
                'conversion_at' => now(),
                'status' => 'active',
            ]);

            $stageId = $this->stageIdForFunnel($prospect->prospect_funnel_id, 'became-client');

            if ($stageId) {
                $this->funnelService->moveStage($prospect, $actor, $stageId, 'conversion');
            }

            $conversion = ProspectConversion::create([
                'prospect_id' => $prospect->id,
                'converted_by' => $actor->id,
                'conversion_type' => 'client',
                'converted_at' => now(),
                'policy_reference' => $policyReference,
                'application_reference' => $applicationReference,
                'notes' => $notes,
            ]);

            $this->shareService->logAccess($prospect, $actor, 'conversion_client', [
                'conversion_id' => $conversion->id,
                'policy_reference' => $policyReference,
            ]);

            event(new ProspectConverted($prospect->fresh(), $actor, $conversion, 'initiated'));

            return $conversion;
        });
    }

    public function convertToInactive(Prospect $prospect, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($prospect, $actor, $reason): void {
            $prospect->update([
                'is_archived' => true,
                'archived_at' => now(),
                'status' => 'inactive',
                'lost_reason' => $reason,
            ]);

            $this->shareService->logAccess($prospect, $actor, 'conversion_inactive', [
                'reason' => $reason,
            ]);
        });
    }

    private function upsertAssociateConversion(Prospect $prospect, User $actor, ?string $notes): ProspectConversion
    {
        $existing = ProspectConversion::query()
            ->where('prospect_id', $prospect->id)
            ->where('conversion_type', 'associate')
            ->whereNull('created_user_id')
            ->first();

        if ($existing) {
            $existing->update([
                'converted_by' => $actor->id,
                'converted_at' => now(),
                'notes' => $notes,
            ]);

            return $existing->fresh();
        }

        return ProspectConversion::create([
            'prospect_id' => $prospect->id,
            'converted_by' => $actor->id,
            'conversion_type' => 'associate',
            'converted_at' => now(),
            'notes' => $notes,
        ]);
    }

    private function isRecruitingFunnel(Prospect $prospect): bool
    {
        if ($prospect->funnel_type === 'recruiting') {
            return true;
        }

        return $prospect->funnel?->key === 'recruiting';
    }

    private function stageIdForFunnel(?int $funnelId, string $pipelineSlug): ?int
    {
        $pipelineStageId = DB::table('pipeline_stages')
            ->whereNull('user_id')
            ->where('slug', $pipelineSlug)
            ->where('is_active', true)
            ->value('id');

        if (! $pipelineStageId) {
            return null;
        }

        if (! $funnelId) {
            return (int) $pipelineStageId;
        }

        $linkedStageId = DB::table('prospect_funnel_stages')
            ->where('prospect_funnel_id', $funnelId)
            ->where('pipeline_stage_id', $pipelineStageId)
            ->value('pipeline_stage_id');

        return $linkedStageId ? (int) $linkedStageId : (int) $pipelineStageId;
    }
}
