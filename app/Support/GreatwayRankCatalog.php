<?php

namespace App\Support;

use App\Models\Rank;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GreatwayRankCatalog
{
    public static function entryCode(): string
    {
        return (string) config('greatway-compensation.entry_rank', 'TA');
    }

    public static function associateCode(): string
    {
        return (string) config('greatway-compensation.associate_rank', 'ASC');
    }

    public static function agencyLeaderCode(): string
    {
        return (string) config('greatway-compensation.agency_leader_rank', 'SVP');
    }

    public static function fieldLeaderCode(): string
    {
        return (string) config('greatway-compensation.field_leader_rank', 'SVP');
    }

    public static function defaultGoalRank(): string
    {
        return (string) config('greatway-compensation.default_goal_rank', 'SA');
    }

    public static function entryName(): string
    {
        return (string) (self::definition(self::entryCode())['name'] ?? 'Training Associate');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function ranks(): array
    {
        return config('greatway-compensation.ranks', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $code): ?array
    {
        $ranks = self::ranks();

        return $ranks[$code] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function legacyCodeMap(): array
    {
        return config('greatway-compensation.legacy_code_map', []);
    }

    public static function resolveCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $legacy = self::legacyCodeMap();

        return $legacy[$code] ?? $code;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function ladder(): array
    {
        return collect(self::ranks())
            ->sortBy('sort_order')
            ->map(fn (array $rank, string $code) => [
                'code' => $code,
                'name' => $rank['name'],
                'track' => $rank['track'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{production: float|int, recruits: float|int, licensing: float|int, fap: float|int, training: float|int}>
     */
    public static function goalRequirements(): array
    {
        return collect(self::ranks())
            ->mapWithKeys(fn (array $rank, string $code) => [$code => $rank['goal_funnel'] ?? [
                'production' => 0,
                'recruits' => 0,
                'licensing' => 100,
                'fap' => 100,
                'training' => 100,
            ]])
            ->all();
    }

    /**
     * Remap leftover legacy rank codes onto current Greatway codes, then merge users if both rows exist.
     */
    public static function remapLegacyRanks(): void
    {
        foreach (self::legacyCodeMap() as $oldCode => $newCode) {
            $old = Rank::query()->withTrashed()->where('code', $oldCode)->first();
            $new = Rank::query()->withTrashed()->where('code', $newCode)->first();

            if ($old === null) {
                continue;
            }

            if ($new === null) {
                $old->forceFill(['code' => $newCode])->save();

                continue;
            }

            if ($old->id === $new->id) {
                continue;
            }

            if (Schema::hasTable('users')) {
                DB::table('users')->where('rank_id', $old->id)->update(['rank_id' => $new->id]);
            }

            if (Schema::hasTable('cfm_promotions')) {
                DB::table('cfm_promotions')->where('current_rank_id', $old->id)->update(['current_rank_id' => $new->id]);
                DB::table('cfm_promotions')->where('target_rank_id', $old->id)->update(['target_rank_id' => $new->id]);
            }

            $old->forceDelete();
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function seederPayload(): Collection
    {
        return collect(self::ranks())->map(function (array $rank, string $code): array {
            return [
                'code' => $code,
                'name' => $rank['name'],
                'sort_order' => $rank['sort_order'],
                'description' => $rank['description'] ?? null,
                'is_active' => true,
                'track' => $rank['track'],
                'income_metric' => $rank['income_metric'],
                'income_requirement' => $rank['income_requirement'],
                'qualification' => $rank['qualification'],
                'nba_requirement' => $rank['nba_requirement'],
                'mb_leg_requirement' => $rank['mb_leg_requirement'],
                'svp_leg_requirement' => $rank['svp_leg_requirement'],
                'svp_leg_alternate' => $rank['svp_leg_alternate'],
                'compensation_rate' => $rank['compensation_rate'],
                'ppb_rate' => $rank['ppb_rate'],
                'total_fyo_rate' => $rank['total_fyo_rate'],
                'non_fyc_rate' => $rank['non_fyc_rate'],
                'is_field_leader' => $rank['is_field_leader'],
            ];
        })->values();
    }
}
