<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\RankRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                $model = RankRequirement::withTrashed()->firstOrCreate(
                    [
                        'rank_id' => $rankId,
                        'code' => Str::slug($requirement['title']),
                    ],
                    [
                        'title' => $requirement['title'],
                        'description' => $requirement['description'] ?? null,
                        'category' => $requirement['category'] ?? 'general',
                        'is_required' => $requirement['is_required'] ?? true,
                        'is_active' => true,
                        'sort_order' => $requirement['sort_order'] ?? 0,
                    ],
                );

                if ($model->trashed()) {
                    $model->restore();
                }
            }
        }
    }
}
