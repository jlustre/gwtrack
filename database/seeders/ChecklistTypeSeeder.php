<?php

namespace Database\Seeders;

use App\Models\ChecklistType;
use Illuminate\Database\Seeder;

class ChecklistTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types =         [
          0 => 
          [
            'code' => 'onboarding',
            'name' => 'Onboarding',
            'description' => 'Steps new members complete during onboarding and profile setup.',
            'icon' => 'clipboard',
            'sort_order' => 10,
            'max_complete_days' => 7,
            'prerequisite_codes' => 
            [
            ],
            'is_active' => true,
          ],
          1 => 
          [
            'code' => 'licensing',
            'name' => 'Licensing',
            'description' => 'Provincial or state licensing milestones and compliance requirements.',
            'icon' => 'badge',
            'sort_order' => 20,
            'max_complete_days' => 60,
            'prerequisite_codes' => 
            [
            ],
            'is_active' => true,
          ],
          2 => 
          [
            'code' => 'fap',
            'name' => 'Field Apprenticeship Program',
            'description' => 'Field Apprenticeship Program milestones for associates and trainees.',
            'icon' => 'academic',
            'sort_order' => 30,
            'max_complete_days' => 30,
            'prerequisite_codes' => 
            [
            ],
            'is_active' => true,
          ],
          3 => 
          [
            'code' => 'cfm-training',
            'name' => 'CFM Training',
            'description' => 'Certified Field Mentor certification training modules and requirements.',
            'icon' => 'book',
            'sort_order' => 40,
            'max_complete_days' => 45,
            'prerequisite_codes' => 
            [
              0 => 'onboarding',
              1 => 'licensing',
              2 => 'fap',
            ],
            'is_active' => true,
          ],
          4 => 
          [
            'code' => 'cfm-mentoring',
            'name' => 'CFM Mentoring',
            'description' => 'Mentor-led trainee checklist items tracked by assigned CFMs.',
            'icon' => 'mentor',
            'sort_order' => 50,
            'max_complete_days' => 120,
            'prerequisite_codes' => 
            [
            ],
            'is_active' => true,
          ],
          5 => 
          [
            'code' => 'training',
            'name' => 'Training',
            'description' => 'General training modules, lessons, and completion tracking.',
            'icon' => 'layers',
            'sort_order' => 60,
            'max_complete_days' => NULL,
            'prerequisite_codes' => 
            [
            ],
            'is_active' => true,
          ],
          6 => 
          [
            'code' => 'rank-advancement-sm',
            'name' => 'Rank Advancement To SM',
            'description' => 'Rank promotion requirements and advancement milestones to SM rank.',
            'icon' => 'rank',
            'sort_order' => 70,
            'max_complete_days' => 45,
            'prerequisite_codes' => 
            [
              0 => 'onboarding',
              1 => 'licensing',
              2 => 'fap',
            ],
            'is_active' => true,
          ],
        ];

        foreach ($types as $type) {
            $checklistType = ChecklistType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'],
                    'sort_order' => $type['sort_order'],
                    'max_complete_days' => $type['max_complete_days'],
                    'is_active' => $type['is_active'],
                ],
            );

            $prerequisiteIds = collect($type['prerequisite_codes'] ?? [])
                ->map(fn (string $code) => ChecklistType::query()->where('code', $code)->value('id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $checklistType->prerequisites()->sync($prerequisiteIds);
        }
    }
}
