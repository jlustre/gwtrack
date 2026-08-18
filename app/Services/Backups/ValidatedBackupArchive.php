<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class ValidatedBackupArchive
{
    public function __construct(
        public readonly string $extractPath,
        public readonly array $metadata,
        public readonly array $checksums,
        public readonly array $tables,
        public readonly array $recordCounts,
    ) {
    }

    public function tablePayload(string $table): array
    {
        if (! in_array($table, $this->tables, true)) {
            throw new RuntimeException("Table [{$table}] is not present in the backup archive.");
        }

        $path = "{$this->extractPath}/tables/{$table}.json";

        if (! File::exists($path)) {
            throw new RuntimeException("Backup data for table [{$table}] is missing.");
        }

        $payload = json_decode((string) File::get($path), true);

        if (! is_array($payload)) {
            throw new RuntimeException("Backup data for table [{$table}] is invalid JSON.");
        }

        return $payload;
    }

    public function cleanup(): void
    {
        File::deleteDirectory($this->extractPath);
    }
}
