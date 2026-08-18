<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class BackupValidationService
{
    public function __construct(
        private readonly BackupClassificationService $classification,
        private readonly BackupStorageService $storage,
    ) {
    }

    public function validateStored(string $relativePath, ?string $disk = null): ValidatedBackupArchive
    {
        return $this->validatePath($this->storage->absolutePath($relativePath, $disk));
    }

    public function validatePath(string $path): ValidatedBackupArchive
    {
        if (! File::exists($path)) {
            throw new RuntimeException('Backup file was not found.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Backup file is not a readable ZIP archive.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name)) {
                $zip->close();

                throw new RuntimeException('Backup archive contains an unsafe file path.');
            }
        }

        $extractPath = storage_path('app/backups/validation/'.Str::uuid()->toString());
        File::ensureDirectoryExists($extractPath);

        if (! $zip->extractTo($extractPath)) {
            $zip->close();

            throw new RuntimeException('Unable to extract backup archive.');
        }

        $zip->close();

        try {
            $metadata = $this->readJson("{$extractPath}/metadata.json");
            $checksums = File::exists("{$extractPath}/checksums.json")
                ? $this->readJson("{$extractPath}/checksums.json")
                : [];

            $tables = $this->tableNames($extractPath, $metadata);
            $approvedTables = $this->classification->approvedTables();

            foreach ($tables as $table) {
                if (! preg_match('/^[A-Za-z0-9_]+$/', $table) || ! in_array($table, $approvedTables, true)) {
                    throw new RuntimeException("Backup contains an unapproved table [{$table}].");
                }

                $payloadPath = "{$extractPath}/tables/{$table}.json";
                $payload = $this->readJson($payloadPath);

                if (($payload['table'] ?? null) !== $table || ! isset($payload['columns'], $payload['records']) || ! is_array($payload['records'])) {
                    throw new RuntimeException("Backup payload for table [{$table}] is invalid.");
                }

                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Current database does not contain table [{$table}].");
                }

                $currentColumns = Schema::getColumnListing($table);
                $missingColumns = array_diff($payload['columns'], $currentColumns);

                if ($missingColumns !== []) {
                    throw new RuntimeException("Current schema is missing columns for table [{$table}]: ".implode(', ', $missingColumns));
                }

                if (isset($checksums[$table]) && hash_file('sha256', $payloadPath) !== $checksums[$table]) {
                    throw new RuntimeException("Checksum mismatch for table [{$table}].");
                }
            }

            $recordCounts = $metadata['record_counts'] ?? [];

            return new ValidatedBackupArchive($extractPath, $metadata, $checksums, $tables, is_array($recordCounts) ? $recordCounts : []);
        } catch (RuntimeException $exception) {
            File::deleteDirectory($extractPath);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException('Backup archive is missing '.basename($path).'.');
        }

        $data = json_decode((string) File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException('Backup archive contains invalid JSON in '.basename($path).'.');
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function tableNames(string $extractPath, array $metadata): array
    {
        $tables = $metadata['included_tables'] ?? [];

        if (! is_array($tables) || $tables === []) {
            $tables = collect(File::glob("{$extractPath}/tables/*.json"))
                ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
                ->values()
                ->all();
        }

        return array_values(array_unique(array_map('strval', $tables)));
    }
}
