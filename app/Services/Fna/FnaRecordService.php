<?php

namespace App\Services\Fna;

use App\Models\FnaActivityLog;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FnaRecordService
{
    public function __construct(
        private FnaCompletenessService $completeness,
        private FnaProspectBridge $prospectBridge,
    ) {}
    public function create(User $user, array $attributes = [], ?Prospect $prospect = null): FnaRecord
    {
        return DB::transaction(function () use ($user, $attributes, $prospect): FnaRecord {
            $clientName = $attributes['client_name']
                ?? ($prospect?->displayName())
                ?? 'New FNA Client';

            $fna = FnaRecord::create([
                'owner_user_id' => $user->id,
                'created_by_user_id' => $user->id,
                'prospect_id' => $prospect?->id ?? ($attributes['prospect_id'] ?? null),
                'status' => 'draft',
                'title' => $attributes['title'] ?? "{$clientName} FNA",
                'reference_code' => $this->generateReferenceCode(),
                'client_name' => $clientName,
                'current_step' => 1,
                'completeness_score' => 0,
                'dime_completed' => false,
                'is_client_portal' => (bool) ($attributes['is_client_portal'] ?? false),
            ]);

            $this->createEmptySections($fna);

            if ($prospect) {
                $this->prefillFromProspect($fna, $prospect);
            }
            $this->logActivity($fna, $user, 'created', 'FNA record created.');

            if ($fna->prospect_id) {
                $this->prospectBridge->syncProspectFnaStatus($fna->fresh());
                $this->prospectBridge->logProspectTimeline($fna->fresh(), $user, 'FNA record created and linked to this prospect.');
            }

            return $fna->fresh([
                'household',
                'incomeDetail',
                'debtDetail',
                'assetDetail',
                'existingCoverage',
                'goals',
                'riskAssessment',
                'dimeAnalysis',
            ]);
        });
    }

    public function prefillFromProspect(FnaRecord $fna, Prospect $prospect): FnaRecord
    {
        $fna->update([
            'prospect_id' => $prospect->id,
            'client_name' => $prospect->displayName(),
            'client_email' => $prospect->email,
            'client_phone' => $prospect->phone,
            'date_of_birth' => $prospect->date_of_birth,
            'age' => $prospect->date_of_birth?->age,
            'gender' => $prospect->gender,
            'marital_status' => $prospect->marital_status,
            'occupation' => $prospect->occupation,
            'employer_business' => $prospect->employer_business,
            'city' => $prospect->city,
            'state_province' => $prospect->state_province,
            'country' => $prospect->country,
            'title' => "{$prospect->displayName()} FNA",
        ]);

        $fna->household()?->update([
            'children_count' => $prospect->children_count,
        ]);

        return $fna->fresh();
    }

    public function logActivity(FnaRecord $fna, ?User $user, string $action, ?string $description = null, array $metadata = []): FnaActivityLog
    {
        return FnaActivityLog::create([
            'fna_record_id' => $fna->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function resolveCfmForOwner(User $owner): ?User
    {
        $assignment = MentorAssignment::query()
            ->where('apprentice_id', $owner->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        return $assignment?->mentor;
    }

    protected function createEmptySections(FnaRecord $fna): void
    {
        $fna->household()->create([]);
        $fna->incomeDetail()->create([]);
        $fna->debtDetail()->create([]);
        $fna->assetDetail()->create([]);
        $fna->existingCoverage()->create([]);
        $fna->goals()->create(['selected_goals' => []]);
        $fna->riskAssessment()->create([]);
        $fna->dimeAnalysis()->create([
            'total_debt' => 0,
            'total_dime_need' => 0,
            'estimated_protection_gap' => 0,
        ]);
    }

    protected function generateReferenceCode(): string
    {
        $year = now()->format('Y');
        $prefix = "FNA-{$year}-";

        $latest = FnaRecord::withTrashed()
            ->where('reference_code', 'like', "{$prefix}%")
            ->orderByDesc('reference_code')
            ->value('reference_code');

        $sequence = $latest
            ? ((int) substr($latest, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function saveWizardData(FnaRecord $fna, array $data): FnaRecord
    {
        return DB::transaction(function () use ($fna, $data): FnaRecord {
            $recordFields = collect([
                'client_name', 'client_email', 'client_phone', 'date_of_birth', 'age', 'gender',
                'marital_status', 'occupation', 'employer_business', 'city', 'state_province', 'country',
                'preferred_contact_method', 'best_contact_time', 'main_needs_identified',
                'recommended_next_action', 'follow_up_date', 'associate_recommendation', 'summary_notes',
                'current_step',
            ])->filter(fn (string $key): bool => array_key_exists($key, $data));

            if ($recordFields->isNotEmpty()) {
                $updates = $recordFields->mapWithKeys(fn (string $key): array => [$key => $data[$key]])->all();

                if (! empty($data['date_of_birth'])) {
                    $updates['age'] = \Carbon\Carbon::parse($data['date_of_birth'])->age;
                }

                $fna->update($updates);
            }

            if (isset($data['household']) && is_array($data['household'])) {
                $fna->household()->updateOrCreate([], $data['household']);
            }

            if (isset($data['income']) && is_array($data['income'])) {
                $fna->incomeDetail()->updateOrCreate([], $data['income']);
            }

            if (isset($data['debt']) && is_array($data['debt'])) {
                $debt = $data['debt'];
                $debt['total_debt'] = collect([
                    $debt['mortgage_balance'] ?? 0,
                    $debt['credit_card_debt'] ?? 0,
                    $debt['car_loans'] ?? 0,
                    $debt['student_loans'] ?? 0,
                    $debt['personal_loans'] ?? 0,
                    $debt['business_debt'] ?? 0,
                    $debt['other_liabilities'] ?? 0,
                ])->sum(fn ($v) => (float) $v);
                $fna->debtDetail()->updateOrCreate([], $debt);
            }

            if (isset($data['assets']) && is_array($data['assets'])) {
                $assets = $data['assets'];
                $assets['total_assets'] = collect([
                    $assets['emergency_fund'] ?? 0,
                    $assets['checking_savings'] ?? 0,
                    $assets['retirement_accounts'] ?? 0,
                    $assets['investment_accounts'] ?? 0,
                    $assets['real_estate_assets'] ?? 0,
                    $assets['business_assets'] ?? 0,
                    $assets['college_savings'] ?? 0,
                    $assets['other_assets'] ?? 0,
                ])->sum(fn ($v) => (float) $v);
                $fna->assetDetail()->updateOrCreate([], $assets);
            }

            if (isset($data['coverage']) && is_array($data['coverage'])) {
                $fna->existingCoverage()->updateOrCreate([], $data['coverage']);
            }

            if (isset($data['goals']) && is_array($data['goals'])) {
                $fna->goals()->updateOrCreate([], $data['goals']);
            }

            if (isset($data['risk']) && is_array($data['risk'])) {
                $fna->riskAssessment()->updateOrCreate([], $data['risk']);
            }

            $fna->update(['completeness_score' => $this->completeness->score($fna->fresh())]);

            return $fna->fresh([
                'household', 'incomeDetail', 'debtDetail', 'assetDetail',
                'existingCoverage', 'goals', 'riskAssessment', 'dimeAnalysis',
            ]);
        });
    }
}
