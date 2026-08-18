<?php

namespace App\Services\Crm;

use App\Models\CrmPipeline;
use Illuminate\Support\Facades\File;

class CrmFunnelConfigExporter
{
    public function configPath(): string
    {
        return config_path('crm-funnels.php');
    }

    public function export(): string
    {
        $pipelines = CrmPipeline::query()
            ->with(['stages' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (CrmPipeline $pipeline): array {
                $stages = [];
                $terminal = [];

                foreach ($pipeline->stages as $stage) {
                    $stages[$stage->name] = filled($stage->description)
                        ? (string) $stage->description
                        : $stage->name.' stage in '.$pipeline->name.'.';

                    if ($stage->is_terminal) {
                        $terminal[] = $stage->name;
                    }
                }

                return [
                    'key' => $pipeline->key,
                    'name' => $pipeline->name,
                    'short_name' => $pipeline->short_name,
                    'color' => $pipeline->color ?: '#2D3E50',
                    'description' => $pipeline->description,
                    'stages' => $stages,
                    'terminal' => $terminal,
                ];
            })
            ->values()
            ->all();

        $exported = $this->exportPhpArray(['pipelines' => $pipelines], 0);

        $contents = <<<PHP
<?php

/**
 * Independent CRM funnel pipelines and stages.
 * Each pipeline is managed separately; contacts/members may participate in many at once.
 *
 * Stage entries are name => description (stored on crm_pipeline_stages.description).
 * This file is regenerated from Admin → CRM Pipelines & Stages → Update Seeder.
 */
return {$exported};

PHP;

        File::put($this->configPath(), $contents);

        return $this->configPath();
    }

    private function exportPhpArray(mixed $value, int $indent = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $isList = array_is_list($value);
        $pad = str_repeat('    ', $indent);
        $innerPad = str_repeat('    ', $indent + 1);
        $lines = ['['];

        foreach ($value as $key => $item) {
            $exportedItem = $this->exportPhpArray($item, $indent + 1);

            if ($isList) {
                $lines[] = $innerPad.$exportedItem.',';
                continue;
            }

            $exportedKey = var_export($key, true);
            $lines[] = $innerPad.$exportedKey.' => '.$exportedItem.',';
        }

        $lines[] = $pad.']';

        return implode(PHP_EOL, $lines);
    }
}
