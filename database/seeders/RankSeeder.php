<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Support\GreatwayRankCatalog;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        GreatwayRankCatalog::remapLegacyRanks();

        foreach (GreatwayRankCatalog::seederPayload() as $rank) {
            $model = Rank::withTrashed()->firstOrCreate(
                ['code' => $rank['code']],
                $rank,
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }

        $keep = array_keys(GreatwayRankCatalog::ranks());

        Rank::query()
            ->whereNotIn('code', $keep)
            ->whereIn('code', array_keys(GreatwayRankCatalog::legacyCodeMap()))
            ->get()
            ->each(fn (Rank $rank) => $rank->delete());
    }
}
