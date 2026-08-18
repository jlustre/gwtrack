<?php

namespace Database\Seeders;

use App\Models\ProfileCompletionField;
use Illuminate\Database\Seeder;

class ProfileCompletionFieldSeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 10;

        foreach (ProfileCompletionField::definitions() as $fieldKey => $definition) {
            ProfileCompletionField::query()->updateOrCreate(
                ['field_key' => $fieldKey],
                [
                    'label' => $definition['label'],
                    'source' => $definition['source'],
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );

            $sortOrder += 10;
        }
    }
}
