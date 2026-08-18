<?php

namespace Database\Seeders;

use App\Models\GoalBadge;
use Illuminate\Database\Seeder;

class GoalBadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['slug' => 'first_recruit', 'name' => 'First Recruit', 'description' => 'Recruited your first associate.', 'level' => 'bronze', 'sort_order' => 10],
            ['slug' => 'first_policy', 'name' => 'First Policy', 'description' => 'Issued your first policy.', 'level' => 'bronze', 'sort_order' => 20],
            ['slug' => 'first_licensed_associate', 'name' => 'First Licensed Associate', 'description' => 'Helped a recruit become licensed.', 'level' => 'silver', 'sort_order' => 30],
            ['slug' => 'fap_graduate', 'name' => 'FAP Graduate', 'description' => 'Completed the Field Apprenticeship Program.', 'level' => 'gold', 'sort_order' => 40],
            ['slug' => 'top_producer', 'name' => 'Top Producer', 'description' => 'Hit a major production milestone.', 'level' => 'platinum', 'sort_order' => 50],
            ['slug' => 'leadership_builder', 'name' => 'Leadership Builder', 'description' => 'Developed a new leader on your team.', 'level' => 'diamond', 'sort_order' => 60],
        ];

        foreach ($badges as $badge) {
            $criteria = config('goals.badge_criteria.'.$badge['slug'], []);

            GoalBadge::query()->updateOrCreate(
                ['slug' => $badge['slug']],
                array_merge($badge, ['is_active' => true, 'criteria' => $criteria]),
            );
        }
    }
}
