<?php

namespace App\Services\Prospects;

use App\Models\Contact;
use App\Models\ContactReferral;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\RecruitingOpportunity;
use App\Models\SalesOpportunity;
use Illuminate\Support\Facades\DB;

class CrmRelationshipExpansionService
{
    /**
     * Sync a legacy prospect into Phase 6 CRM relationship records.
     *
     * @return array{contact: Contact, sales_opportunity: SalesOpportunity|null, recruiting_opportunity: RecruitingOpportunity|null}
     */
    public function syncProspect(Prospect $prospect): array
    {
        return DB::transaction(function () use ($prospect): array {
            $prospect = Prospect::query()->whereKey($prospect->id)->lockForUpdate()->firstOrFail();
            $contact = $this->contactForProspect($prospect);
            $salesOpportunity = $this->shouldCreateSalesOpportunity($prospect)
                ? $this->salesOpportunityForProspect($prospect, $contact)
                : null;
            $recruitingOpportunity = $this->shouldCreateRecruitingOpportunity($prospect)
                ? $this->recruitingOpportunityForProspect($prospect, $contact)
                : null;

            $prospect->forceFill([
                'contact_id' => $contact->id,
                'sales_opportunity_id' => $salesOpportunity?->id,
                'recruiting_opportunity_id' => $recruitingOpportunity?->id,
            ])->save();

            return [
                'contact' => $contact->fresh(),
                'sales_opportunity' => $salesOpportunity?->fresh(),
                'recruiting_opportunity' => $recruitingOpportunity?->fresh(),
            ];
        });
    }

    public function contactForProspect(Prospect $prospect): Contact
    {
        $prospect->loadMissing(['owner', 'source']);

        return Contact::query()->updateOrCreate(
            ['source_prospect_id' => $prospect->id],
            [
                'owner_id' => $prospect->owner_id,
                'contact_type' => $this->contactTypeFor($prospect),
                'relationship_status' => $prospect->is_archived ? 'archived' : ($prospect->status ?? 'active'),
                'first_name' => $prospect->first_name,
                'last_name' => $prospect->last_name,
                'preferred_name' => $prospect->preferred_name,
                'email' => $prospect->email,
                'phone' => $prospect->phone,
                'secondary_phone' => $prospect->secondary_phone,
                'city' => $prospect->city,
                'state_province' => $prospect->state_province,
                'country' => $prospect->country,
                'timezone' => $prospect->timezone,
                'preferred_language' => $prospect->preferred_language,
                'occupation' => $prospect->occupation,
                'employer_business' => $prospect->employer_business,
                'notes_summary' => $prospect->notes_summary,
                'metadata' => [
                    'legacy_prospect_id' => $prospect->id,
                    'funnel_type' => $prospect->funnel_type,
                    'source' => $prospect->source?->name,
                    'referral_source_name' => $prospect->referral_source_name,
                    'campaign_name' => $prospect->campaign_name,
                ],
            ],
        );
    }

    public function salesOpportunityForProspect(Prospect $prospect, Contact $contact): SalesOpportunity
    {
        $opportunity = SalesOpportunity::query()->firstOrNew(['prospect_id' => $prospect->id]);
        $isNew = ! $opportunity->exists;
        $status = $this->opportunityStatusFor($prospect, 'insurance');

        $opportunity->fill([
            'contact_id' => $contact->id,
            'owner_id' => $prospect->owner_id,
            'prospect_funnel_id' => $prospect->prospect_funnel_id,
            'pipeline_stage_id' => $prospect->pipeline_stage_id,
            'title' => $prospect->displayName().' - Insurance Opportunity',
            'status' => $status,
            'opportunity_type' => 'insurance',
            'opened_at' => $opportunity->opened_at ?? $prospect->created_at ?? now(),
            'closed_at' => $this->closedAtFor($opportunity, $prospect, $status),
            'lost_reason' => $prospect->lost_reason,
            'metadata' => [
                ...($opportunity->metadata ?? []),
                'funnel_type' => $prospect->funnel_type,
                'interest_level' => $prospect->interest_level,
                'priority' => $prospect->priority,
            ],
        ]);
        if ($isNew) {
            $opportunity->probability = $this->probabilityFor($prospect);
        }
        $opportunity->save();

        return $opportunity;
    }

    public function recruitingOpportunityForProspect(Prospect $prospect, Contact $contact): RecruitingOpportunity
    {
        $opportunity = RecruitingOpportunity::query()->firstOrNew(['prospect_id' => $prospect->id]);
        $isNew = ! $opportunity->exists;
        $status = $this->opportunityStatusFor($prospect, 'recruiting');

        $opportunity->fill([
            'contact_id' => $contact->id,
            'owner_id' => $prospect->owner_id,
            'prospect_funnel_id' => $prospect->prospect_funnel_id,
            'pipeline_stage_id' => $prospect->pipeline_stage_id,
            'title' => $prospect->displayName().' - Recruiting Opportunity',
            'status' => $status,
            'candidate_type' => 'associate',
            'opened_at' => $opportunity->opened_at ?? $prospect->created_at ?? now(),
            'closed_at' => $this->closedAtFor($opportunity, $prospect, $status),
            'lost_reason' => $prospect->lost_reason,
            'metadata' => [
                ...($opportunity->metadata ?? []),
                'funnel_type' => $prospect->funnel_type,
                'interest_level' => $prospect->interest_level,
                'priority' => $prospect->priority,
            ],
        ]);
        if ($isNew) {
            $opportunity->probability = $this->probabilityFor($prospect);
        }
        $opportunity->save();

        return $opportunity;
    }

    public function referralFromProspects(
        Prospect $referred,
        Prospect|Contact|null $referrer,
        ?string $sourceLabel = null,
        ?string $notes = null,
    ): ContactReferral {
        $referredContact = $referred->contact ?: $this->contactForProspect($referred);

        return ContactReferral::query()->create([
            'owner_id' => $referred->owner_id,
            'referrer_contact_id' => $referrer instanceof Contact ? $referrer->id : $referrer?->contact_id,
            'referrer_prospect_id' => $referrer instanceof Prospect ? $referrer->id : null,
            'referred_contact_id' => $referredContact->id,
            'referred_prospect_id' => $referred->id,
            'referral_type' => $referred->funnel_type === 'recruiting' ? 'recruiting' : 'prospect',
            'status' => 'new',
            'source_label' => $sourceLabel ?? $referred->referral_source_name,
            'notes' => $notes,
            'referred_at' => now(),
            'metadata' => [
                'funnel_type' => $referred->funnel_type,
            ],
        ]);
    }

    private function shouldCreateSalesOpportunity(Prospect $prospect): bool
    {
        return in_array($prospect->funnel_type, ['insurance', 'both'], true);
    }

    private function shouldCreateRecruitingOpportunity(Prospect $prospect): bool
    {
        return in_array($prospect->funnel_type, ['recruiting', 'both'], true);
    }

    private function contactTypeFor(Prospect $prospect): string
    {
        return match ($prospect->converted_to) {
            'client' => 'client',
            'associate' => 'associate',
            default => $prospect->funnel_type === 'recruiting' ? 'recruiting_candidate' : 'prospect',
        };
    }

    private function opportunityStatusFor(Prospect $prospect, string $opportunityType): string
    {
        $conversionType = $opportunityType === 'insurance' ? 'client' : 'associate';
        if ($prospect->converted_to === $conversionType) {
            return 'won';
        }

        $stageSlug = $prospect->stage?->slug
            ?? PipelineStage::query()->whereKey($prospect->pipeline_stage_id)->value('slug');
        if ($prospect->is_archived || $prospect->status === 'lost' || $stageSlug === 'closed-lost') {
            return 'lost';
        }

        return 'open';
    }

    private function closedAtFor(
        SalesOpportunity|RecruitingOpportunity $opportunity,
        Prospect $prospect,
        string $status,
    ): mixed {
        if ($status === 'open') {
            return null;
        }

        if ($status === 'won') {
            return $prospect->conversion_at ?? $opportunity->closed_at ?? now();
        }

        return $opportunity->closed_at ?? $prospect->updated_at ?? now();
    }

    private function probabilityFor(Prospect $prospect): int
    {
        return match ($prospect->interest_level) {
            'hot' => 75,
            'warm' => 45,
            'cold' => 15,
            default => 25,
        };
    }
}
