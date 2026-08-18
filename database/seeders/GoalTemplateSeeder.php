<?php

namespace Database\Seeders;

use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use Illuminate\Database\Seeder;

class GoalTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'recruiting' => [
                ['name' => 'Weekly recruits', 'hierarchy_level' => 'weekly', 'measurement_type' => 'number', 'metric_key' => 'recruits', 'default_target' => 1],
                ['name' => 'Monthly recruits', 'hierarchy_level' => 'monthly', 'measurement_type' => 'number', 'metric_key' => 'recruits', 'default_target' => 4],
                ['name' => 'Quarterly recruiting push', 'hierarchy_level' => 'quarterly', 'measurement_type' => 'number', 'metric_key' => 'recruits', 'default_target' => 12],
            ],
            'production' => [
                ['name' => 'Monthly premium target', 'hierarchy_level' => 'monthly', 'measurement_type' => 'currency', 'metric_key' => 'monthly_premium', 'default_target' => 10000],
                ['name' => 'Annual premium goal', 'hierarchy_level' => 'annual', 'measurement_type' => 'currency', 'metric_key' => 'annual_premium', 'default_target' => 120000],
            ],
            'prospecting' => [
                ['name' => 'Weekly contacts', 'hierarchy_level' => 'weekly', 'measurement_type' => 'number', 'metric_key' => 'contacts', 'default_target' => 25],
                ['name' => 'Monthly appointments', 'hierarchy_level' => 'monthly', 'measurement_type' => 'number', 'metric_key' => 'appointments', 'default_target' => 8],
            ],
            'financial_review' => [
                ['name' => 'Monthly FNAs completed', 'hierarchy_level' => 'monthly', 'measurement_type' => 'number', 'metric_key' => 'fna_completed', 'default_target' => 4],
            ],
            'fap' => [
                ['name' => 'Complete FAP program', 'hierarchy_level' => 'quarterly', 'measurement_type' => 'percentage', 'metric_key' => 'fap_completion', 'default_target' => 100],
            ],
            'licensing' => [
                ['name' => 'Complete licensing checklist', 'hierarchy_level' => 'quarterly', 'measurement_type' => 'percentage', 'metric_key' => 'licensing_completion', 'default_target' => 100],
            ],
            'training' => [
                ['name' => 'Training center completion', 'hierarchy_level' => 'monthly', 'measurement_type' => 'percentage', 'metric_key' => 'training_completion', 'default_target' => 100],
            ],
            'rank_advancement' => [
                ['name' => 'Next rank requirements', 'hierarchy_level' => 'annual', 'measurement_type' => 'percentage', 'metric_key' => 'rank_requirements', 'default_target' => 100],
            ],
            'income' => [
                ['name' => 'Annual income target', 'hierarchy_level' => 'annual', 'measurement_type' => 'currency', 'metric_key' => 'annual_income', 'default_target' => 100000],
            ],
        ];

        $sortOrder = 10;

        foreach ($templates as $categorySlug => $items) {
            $categoryId = GoalCategory::query()->where('slug', $categorySlug)->value('id');

            if (! $categoryId) {
                continue;
            }

            foreach ($items as $item) {
                GoalTemplate::query()->updateOrCreate(
                    [
                        'goal_category_id' => $categoryId,
                        'name' => $item['name'],
                    ],
                    array_merge($item, [
                        'goal_category_id' => $categoryId,
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                    ]),
                );

                $sortOrder += 10;
            }
        }
    }
}
