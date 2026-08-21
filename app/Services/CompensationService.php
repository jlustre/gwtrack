<?php

namespace App\Services;

use App\Models\Rank;
use App\Models\User;
use App\Support\GreatwayRankCatalog;
use Illuminate\Support\Carbon;

class CompensationService
{
    /**
     * @return array<string, mixed>|null
     */
    public function snapshotForRank(?Rank $rank): ?array
    {
        if ($rank === null) {
            return null;
        }

        $definition = GreatwayRankCatalog::definition($rank->code) ?? [];
        $track = $rank->track ?? ($definition['track'] ?? 'producer');
        $trackMeta = config('greatway-compensation.tracks.'.$track, []);

        return [
            'code' => $rank->code,
            'name' => $rank->name,
            'track' => $track,
            'track_label' => $trackMeta['label'] ?? ucfirst((string) $track),
            'track_description' => $trackMeta['description'] ?? null,
            'income_metric' => $rank->income_metric,
            'income_metric_label' => $this->incomeMetricLabel($rank->income_metric),
            'income_requirement' => $rank->income_requirement !== null ? (int) $rank->income_requirement : null,
            'income_requirement_label' => $this->incomeRequirementLabel($rank),
            'qualification' => $rank->qualification,
            'nba_requirement' => $rank->nba_requirement,
            'mb_leg_requirement' => $rank->mb_leg_requirement,
            'svp_leg_requirement' => $rank->svp_leg_requirement,
            'svp_leg_alternate' => $rank->svp_leg_alternate,
            'compensation_rate' => $this->toRate($rank->compensation_rate),
            'ppb_rate' => $this->toRate($rank->ppb_rate),
            'total_fyo_rate' => $this->toRate($rank->total_fyo_rate),
            'non_fyc_rate' => $this->toRate($rank->non_fyc_rate),
            'is_field_leader' => (bool) $rank->is_field_leader,
            'description' => $rank->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForUser(User $user): array
    {
        $user->loadMissing('rank');

        $current = $this->snapshotForRank($user->rank);
        $nextRank = $this->nextRank($user);
        $isFieldLeader = (bool) ($user->rank?->is_field_leader ?? false);

        return [
            'current' => $current,
            'next' => $this->snapshotForRank($nextRank),
            'is_field_leader' => $isFieldLeader,
            'generational' => $isFieldLeader ? $this->generationalOverrides() : [],
            'generational_eligible' => $isFieldLeader,
            'consistency_exempt' => $this->isExemptFromGenerationalConsistency($user),
            'consistency' => config('greatway-compensation.generational_consistency', []),
            'bonus_pools' => $this->bonusPoolsFor($user->rank),
            'organization' => config('greatway-compensation.organization', []),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function generationalOverrides(): array
    {
        return array_values(config('greatway-compensation.generational_overrides', []));
    }

    public function isExemptFromGenerationalConsistency(User $user): bool
    {
        $rules = config('greatway-compensation.generational_consistency', []);
        $tenureYears = (int) ($rules['tenure_exemption_years'] ?? 10);
        $leadershipYears = (int) ($rules['leadership_exemption_years'] ?? 5);

        $started = $user->joined_at ?? $user->created_at;
        if (! $started instanceof Carbon) {
            return false;
        }

        if ($started->gt(now()->subYears($tenureYears))) {
            return false;
        }

        if (! $user->rank?->is_field_leader) {
            return false;
        }

        $leadershipStart = $user->joined_at ?? $user->created_at;
        if (! $leadershipStart instanceof Carbon) {
            return false;
        }

        return $leadershipStart->lte(now()->subYears($leadershipYears));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bonusPoolsFor(?Rank $rank): array
    {
        $pools = config('greatway-compensation.bonus_pools', []);
        $code = $rank?->code;
        $isFieldLeader = (bool) ($rank?->is_field_leader);

        $items = [];

        if ($isFieldLeader && isset($pools['baseshop_builder'])) {
            $items[] = $pools['baseshop_builder'];
        }

        foreach (['revenue_sharing_sevc' => 'SEVC', 'revenue_sharing_nevc' => 'NEVC'] as $key => $requiredCode) {
            if (! isset($pools[$key]) || $code === null) {
                continue;
            }

            $required = Rank::query()->where('code', $requiredCode)->first();
            if ($required && $rank && $rank->sort_order >= $required->sort_order) {
                $items[] = $pools[$key];
            }
        }

        return $items;
    }

    public function nextRank(User $user): ?Rank
    {
        $current = $user->rank;

        if ($current === null) {
            return Rank::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();
        }

        return Rank::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $current->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    public function formatPercent(?float $rate): string
    {
        if ($rate === null) {
            return '—';
        }

        $formatted = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

        return $formatted.'%';
    }

    public function incomeRequirementLabel(?Rank $rank): ?string
    {
        if ($rank === null || $rank->income_requirement === null) {
            return $rank?->qualification;
        }

        $amount = '$'.number_format((int) $rank->income_requirement);
        $metric = $this->incomeMetricAbbreviation($rank->income_metric);

        return $metric ? $amount.' '.$metric : $amount;
    }

    private function incomeMetricLabel(?string $metric): ?string
    {
        if ($metric === null || $metric === '') {
            return null;
        }

        return config('greatway-compensation.income_metrics.'.$metric);
    }

    private function incomeMetricAbbreviation(?string $metric): ?string
    {
        return match ($metric) {
            'lti' => 'LTI',
            'twelve_month_income' => '12MI',
            default => null,
        };
    }

    private function toRate(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, 2);
    }
}
