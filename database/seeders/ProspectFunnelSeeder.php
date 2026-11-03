<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProspectFunnelSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('crm-funnels.funnels', []);

        if ($catalog === []) {
            $catalog = $this->legacyCatalogFromPipelines(config('crm-funnels.pipelines', []));
        }

        foreach ($catalog as $key => $funnel) {
            $this->seedPipelineStages($funnel['stages'] ?? []);
        }

        foreach ($catalog as $key => $funnel) {
            $funnelId = $this->upsertFunnel(
                key: (string) $key,
                name: $funnel['name'],
                description: $funnel['description'],
                sortOrder: (int) ($funnel['sort_order'] ?? 100),
                isDefault: (bool) ($funnel['is_default'] ?? false),
            );

            $this->seedFunnelStages($funnelId, $funnel['stages'] ?? []);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $stages
     */
    private function seedPipelineStages(array $stages): void
    {
        foreach ($stages as $index => $stage) {
            $name = (string) ($stage['pipeline_name'] ?? $stage['name']);
            $slug = (string) ($stage['pipeline_slug'] ?? $stage['slug'] ?? Str::slug($name));

            DB::table('pipeline_stages')->updateOrInsert(
                ['user_id' => null, 'slug' => $slug],
                [
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                    'is_terminal' => (bool) ($stage['is_terminal'] ?? false),
                    'is_active' => true,
                    'deleted_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $pipelines
     * @return array<string, array<string, mixed>>
     */
    private function legacyCatalogFromPipelines(array $pipelines): array
    {
        $pipelinesByKey = collect($pipelines)->keyBy('key');
        $catalog = [];

        foreach (['insurance' => 'sales', 'recruiting' => 'recruiting'] as $legacyKey => $pipelineKey) {
            $pipeline = $pipelinesByKey->get($pipelineKey);

            if (! is_array($pipeline)) {
                continue;
            }

            $terminalStages = $pipeline['terminal'] ?? [];
            $stages = [];

            foreach (array_keys($pipeline['stages'] ?? []) as $stageName) {
                $stageSlug = $legacyKey === 'recruiting' && Str::slug($stageName) === 'invitation-sent'
                    ? 'registration-link-sent'
                    : Str::slug($stageName);

                $stages[] = [
                    'name' => $stageName,
                    'slug' => $stageSlug,
                    'pipeline_slug' => $stageSlug,
                    'is_terminal' => in_array($stageName, $terminalStages, true),
                ];
            }

            $catalog[$legacyKey] = [
                'name' => $pipeline['name'],
                'description' => $pipeline['description'] ?? '',
                'sort_order' => $legacyKey === 'insurance' ? 10 : 20,
                'is_default' => $legacyKey === 'insurance',
                'stages' => $stages,
            ];
        }

        return $catalog;
    }

    private function upsertFunnel(string $key, string $name, string $description, int $sortOrder, bool $isDefault = false): int
    {
        if ($isDefault) {
            DB::table('prospect_funnels')
                ->whereNull('user_id')
                ->where('key', '!=', $key)
                ->update(['is_default' => false, 'updated_at' => now()]);
        }

        DB::table('prospect_funnels')->updateOrInsert(
            ['key' => $key],
            [
                'user_id' => null,
                'name' => $name,
                'description' => $description,
                'is_default' => $isDefault,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'deleted_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('prospect_funnels')->where('key', $key)->value('id');
    }

    /**
     * @param  list<array<string, mixed>>  $stages
     */
    private function seedFunnelStages(int $funnelId, array $stages): void
    {
        $activeSlugs = [];

        foreach ($stages as $index => $stage) {
            $stageSlug = (string) ($stage['slug'] ?? Str::slug((string) $stage['name']));
            $pipelineSlug = (string) ($stage['pipeline_slug'] ?? $stageSlug);
            $activeSlugs[] = $stageSlug;

            $pipelineStageId = DB::table('pipeline_stages')
                ->whereNull('user_id')
                ->where('slug', $pipelineSlug)
                ->value('id');

            DB::table('prospect_funnel_stages')->updateOrInsert(
                ['prospect_funnel_id' => $funnelId, 'slug' => $stageSlug],
                [
                    'pipeline_stage_id' => $pipelineStageId,
                    'name' => (string) $stage['name'],
                    'sort_order' => ($index + 1) * 10,
                    'conversion_weight' => (float) ($stage['weight'] ?? max(0, 100 - ($index * 4))),
                    'is_terminal' => (bool) ($stage['is_terminal'] ?? false),
                    'auto_task_template' => isset($stage['auto_task_template'])
                        ? json_encode($stage['auto_task_template'])
                        : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('prospect_funnel_stages')
            ->where('prospect_funnel_id', $funnelId)
            ->whereNotIn('slug', $activeSlugs)
            ->delete();
    }
}
