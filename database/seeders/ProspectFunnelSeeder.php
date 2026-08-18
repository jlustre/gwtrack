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
