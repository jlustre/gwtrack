<?php

namespace App\Services\TodaysAgenda\Support;

trait AgendaSectionBuilder
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     count: int,
     *     items: list<array<string, mixed>>,
     *     empty_message: string,
     *     action: array{label: string, url: string}|null
     * }
     */
    protected function buildSection(
        string $key,
        string $label,
        string $icon,
        array $items,
        string $emptyMessage,
        ?array $action = null,
    ): array {
        usort($items, function (array $left, array $right): int {
            return strcmp((string) ($left['sort_at'] ?? ''), (string) ($right['sort_at'] ?? ''));
        });

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'count' => count($items),
            'items' => $items,
            'empty_message' => $emptyMessage,
            'action' => $action,
        ];
    }
}
