<?php

namespace App\Services\Goals;

class SmartGoalValidator
{
    /**
     * @param  array{
     *     name?: string,
     *     description?: string,
     *     target_value?: float|int|string|null,
     *     measurement_type?: string,
     *     deadline_at?: string|null,
     *     starts_at?: string|null,
     *     metric_key?: string|null,
     *     goal_category_id?: int|null,
     * }  $data
     * @return array{score: int, feedback: list<array{key: string, label: string, passed: bool, suggestion: string|null}>}
     */
    public function evaluate(array $data): array
    {
        $checks = [
            $this->checkSpecific($data),
            $this->checkMeasurable($data),
            $this->checkAchievable($data),
            $this->checkRelevant($data),
            $this->checkTimeBound($data),
        ];

        $passed = collect($checks)->where('passed', true)->count();

        return [
            'score' => (int) round(($passed / count($checks)) * 100),
            'feedback' => $checks,
        ];
    }

  /**
   * @param  array<string, mixed>  $data
   * @return array{key: string, label: string, passed: bool, suggestion: string|null}
   */
    private function checkSpecific(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $passed = strlen($name) >= 5 && (strlen($description) >= 20 || filled($data['metric_key'] ?? null));

        return [
            'key' => 'specific',
            'label' => 'Specific',
            'passed' => $passed,
            'suggestion' => $passed ? null : 'Add a clear goal name and a short description of what success looks like.',
        ];
    }

    private function checkMeasurable(array $data): array
    {
        $target = (float) ($data['target_value'] ?? 0);
        $passed = $target > 0 || ($data['measurement_type'] ?? '') === 'completion';

        return [
            'key' => 'measurable',
            'label' => 'Measurable',
            'passed' => $passed,
            'suggestion' => $passed ? null : 'Set a numeric target or choose completion as the measurement type.',
        ];
    }

    private function checkAchievable(array $data): array
    {
        $target = (float) ($data['target_value'] ?? 0);
        $passed = $target <= 0 || $target <= 10000;

        return [
            'key' => 'achievable',
            'label' => 'Achievable',
            'passed' => $passed,
            'suggestion' => $passed ? null : 'Consider breaking very large targets into milestones or a parent goal hierarchy.',
        ];
    }

    private function checkRelevant(array $data): array
    {
        $passed = filled($data['goal_category_id'] ?? null);

        return [
            'key' => 'relevant',
            'label' => 'Relevant',
            'passed' => $passed,
            'suggestion' => $passed ? null : 'Select a goal category aligned with your business priorities.',
        ];
    }

    private function checkTimeBound(array $data): array
    {
        $passed = filled($data['deadline_at'] ?? null);

        return [
            'key' => 'time_bound',
            'label' => 'Time-bound',
            'passed' => $passed,
            'suggestion' => $passed ? null : 'Set a deadline so progress can be tracked against time.',
        ];
    }
}
