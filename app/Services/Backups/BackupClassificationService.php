<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class BackupClassificationService
{
    /**
     * @return list<string>
     */
    public function tablesFor(string $type): array
    {
        return match ($type) {
            'structural' => $this->approved(config('backup-classification.structural_tables', [])),
            'transactional' => $this->approved(config('backup-classification.transactional_tables', [])),
            'combined' => $this->approved([
                ...config('backup-classification.structural_tables', []),
                ...config('backup-classification.transactional_tables', []),
            ]),
            'full' => $this->approved($this->databaseTables()),
            default => throw new InvalidArgumentException("Unknown backup type [{$type}]."),
        };
    }

    /**
     * @return list<string>
     */
    public function approvedTables(): array
    {
        return array_values(array_diff(
            $this->databaseTables(),
            config('backup-classification.excluded_tables', [])
        ));
    }

    /**
     * @param  array<int, string>  $tables
     * @return list<string>
     */
    public function validateTables(array $tables): array
    {
        $approved = $this->approvedTables();

        return array_values(array_filter(
            array_unique($tables),
            fn (string $table): bool => in_array($table, $approved, true)
        ));
    }

    /**
     * @return list<string>
     */
    public function protectedTables(): array
    {
        return config('backup-classification.protected_tables', []);
    }

    /**
     * @param  array<int, string>  $tables
     * @return list<string>
     */
    private function approved(array $tables): array
    {
        return $this->validateTables($tables);
    }

    /**
     * @return list<string>
     */
    private function databaseTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row): string => (string) array_values((array) $row)[0])
            ->sort()
            ->values()
            ->all();
    }
}
