<?php

namespace App\Services\Prospects;

use App\Models\Contact;
use App\Models\ContactReferral;
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
        return SalesOpportunity::query()->updateOrCreate(
            ['prospect_id' => $prospect->id],
            [
                'contact_id' => $contact->id,
                'owner_id' => $prospect->owner_id,
                'prospect_funnel_id' => $prospect->prospect_funnel_id,
                'pipeline_stage_id' => $prospect->pipeline_stage_id,
                'title' => $prospect->displayName().' - Insurance Opportunity',
                'status' => $this->opportunityStatusFor($prospect),
                'opportunity_type' => 'insurance',
                'probability' => $this->probabilityFor($prospect),
                'opened_at' => $prospect->created_at ?? now(),
                'closed_at' => $prospect->converted_to === 'client' ? ($prospect->conversion_at ?? $prospect->updated_at) : null,
                'lost_reason' => $prospect->lost_reason,
                'metadata' => [
                    'funnel_type' => $prospect->funnel_type,
                    'interest_level' => $prospect->interest_level,
                    'priority' => $prospect->priority,
                ],
            ],
        );
    }

    public function recruitingOpportunityForProspect(Prospect $prospect, Contact $contact): RecruitingOpportunity
    {
        return RecruitingOpportunity::query()->updateOrCreate(
            ['prospect_id' => $prospect->id],
            [
                'contact_id' => $contact->id,
                'owner_id' => $prospect->owner_id,
                'prospect_funnel_id' => $prospect->prospect_funnel_id,
                'pipeline_stage_id' => $prospect->pipeline_stage_id,
                'title' => $prospect->displayName().' - Recruiting Opportunity',
                'status' => $this->opportunityStatusFor($prospect),
                'candidate_type' => 'associate',
                'probability' => $this->probabilityFor($prospect),
                'opened_at' => $prospect->created_at ?? now(),
                'closed_at' => $prospect->converted_to === 'associate' ? ($prospect->conversion_at ?? $prospect->updated_at) : null,
                'lost_reason' => $prospect->lost_reason,
                'metadata' => [
                    'funnel_type' => $prospect->funnel_type,
                    'interest_level' => $prospect->interest_level,
                    'priority' => $prospect->priority,
                ],
            ],
        );
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

    private function opportunityStatusFor(Prospect $prospect): string
    {
        if ($prospect->converted_to) {
            return 'won';
        }

        if ($prospect->is_archived || $prospect->status === 'lost') {
            return 'lost';
        }

        return 'open';
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
