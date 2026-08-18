<?php

namespace Database\Seeders;

use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Services\Crm\CrmFunnelService;
use Illuminate\Database\Seeder;

class CrmFunnelSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(CrmFunnelService::class);

        foreach (config('crm-funnels.pipelines', []) as $index => $pipelineData) {
            $pipeline = CrmPipeline::query()->updateOrCreate(
                ['key' => $pipelineData['key']],
                [
                    'name' => $pipelineData['name'],
                    'short_name' => $pipelineData['short_name'] ?? null,
                    'description' => $pipelineData['description'] ?? null,
                    'color' => $pipelineData['color'] ?? '#2D3E50',
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );

            $terminal = collect($pipelineData['terminal'] ?? []);
            $stageIndex = 0;

            foreach ($pipelineData['stages'] ?? [] as $stageKey => $stageValue) {
                $stageIndex++;

                [$stageName, $stageDescription] = is_int($stageKey)
                    ? [(string) $stageValue, null]
                    : [(string) $stageKey, is_string($stageValue) ? $stageValue : null];

                CrmPipelineStage::query()->updateOrCreate(
                    [
                        'crm_pipeline_id' => $pipeline->id,
                        'slug' => $service->makeStageSlug($stageName),
                    ],
                    [
                        'name' => $stageName,
                        'description' => $stageDescription,
                        'color' => $pipeline->color,
                        'sort_order' => $stageIndex * 10,
                        'is_terminal' => $terminal->contains($stageName),
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
