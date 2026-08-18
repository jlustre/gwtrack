<?php

namespace Database\Seeders;

use App\Models\PortalResource;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceDocumentationGuideSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->value('id');
        $sortOrder = 1;

        foreach (config('support-documentation.modules', []) as $module) {
            if (blank($module['slug'] ?? null) || blank($module['file'] ?? null)) {
                continue;
            }

            $title = $module['title']
                ?? (str($module['module'])->endsWith('User Guide')
                    ? $module['module']
                    : $module['module'].' User Guide');

            PortalResource::query()->updateOrCreate(
                [
                    'title' => $title,
                    'type' => 'document',
                ],
                [
                    'created_by' => $creatorId,
                    'description' => $module['summary'] ?? null,
                    'category' => 'guides',
                    'sort_order' => $sortOrder,
                    'is_featured' => true,
                    'is_published' => true,
                    'url' => 'support/documentation/'.$module['slug'],
                    'file_format' => 'GUIDE',
                ],
            );

            $sortOrder++;
        }
    }
}
