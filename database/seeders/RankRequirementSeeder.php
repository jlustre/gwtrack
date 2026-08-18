<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\RankRequirement;
use Illuminate\Database\Seeder;

class RankRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = require __DIR__.'/data/rank_advancement_requirements.php';
        $ranks = Rank::query()->pluck('id', 'code');

        foreach ($definitions as $rankCode => $requirements) {
            $rankId = $ranks->get($rankCode);

            if ($rankId === null) {
                continue;
            }

            foreach ($requirements as $requirement) {
                RankRequirement::query()->updateOrCreate(
                    [
                        'rank_id' => $rankId,
                        'title' => $requirement['title'],
                    ],
                    [
                        'description' => $requirement['description'] ?? null,
                        'category' => $requirement['category'] ?? 'general',
                        'is_required' => $requirement['is_required'] ?? true,
                        'sort_order' => $requirement['sort_order'] ?? 0,
                    ],
                );
            }
        }
    }
}
