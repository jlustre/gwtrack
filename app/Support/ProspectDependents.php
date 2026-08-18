<?php

namespace App\Support;

class ProspectDependents
{
    /**
     * @return list<array{name: string, age: int|null}>
     */
    public static function normalize(?array $dependents): array
    {
        return collect($dependents ?? [])
            ->map(function (mixed $row): array {
                $row = is_array($row) ? $row : [];

                $age = $row['age'] ?? null;
                $age = filled($age) ? max(0, min(99, (int) $age)) : null;

                return [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'age' => $age,
                ];
            })
            ->filter(fn (array $row): bool => $row['name'] !== '' || $row['age'] !== null)
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, age: int|null}>
     */
    public static function formRows(?array $dependents, int $minimumRows = 2): array
    {
        $normalized = self::normalize($dependents);

        if ($normalized === []) {
            return array_fill(0, $minimumRows, ['name' => '', 'age' => null]);
        }

        while (count($normalized) < $minimumRows) {
            $normalized[] = ['name' => '', 'age' => null];
        }

        return $normalized;
    }
}
