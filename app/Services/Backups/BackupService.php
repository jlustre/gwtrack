<?php

namespace App\Services\Backups;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class BackupService
{
    public function __construct(
        private readonly BackupStorageService $storage,
    ) {
    }

    public function create(Backup $backup): Backup
    {
        $backup->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $tables = $backup->included_tables ?? [];
        $workingPath = storage_path("app/backups/work/{$backup->id}");
        File::deleteDirectory($workingPath);
        File::ensureDirectoryExists("{$workingPath}/tables");

        $recordCounts = [];
        $checksums = [];

        foreach ($tables as $table) {
            $payloadPath = "{$workingPath}/tables/{$table}.json";
            $records = $this->recordsFor($table);
            $payload = [
                'table' => $table,
                'columns' => Schema::getColumnListing($table),
                'primary_key' => $this->primaryKey($table),
                'records' => $records,
            ];

            File::put($payloadPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $recordCounts[$table] = count($records);
            $checksums[$table] = hash_file('sha256', $payloadPath);

            $backup->items()->updateOrCreate(
                ['table_name' => $table],
                [
                    'record_count' => count($records),
                    'status' => 'completed',
                    'exported_bytes' => File::size($payloadPath),
                    'error_message' => null,
                    'metadata' => [
                        'columns' => $payload['columns'],
                        'primary_key' => $payload['primary_key'],
                    ],
                ]
            );
        }

        $metadata = [
            'backup_type' => $backup->backup_type,
            'created_by' => $backup->created_by,
            'created_at' => now()->toIso8601String(),
            'included_tables' => $tables,
            'excluded_tables' => $backup->excluded_tables ?? [],
            'app_version' => config('app.version', config('app.name')),
            'database_name' => DB::connection()->getDatabaseName(),
            'record_counts' => $recordCounts,
            'format' => 'gwtrack-json-v1',
        ];

        File::put("{$workingPath}/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put("{$workingPath}/checksums.json", json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $backup->update(['metadata' => $metadata]);

        return $this->storage->storeWorkingDirectory($backup, $workingPath);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recordsFor(string $table): array
    {
        $query = DB::table($table);
        $primaryKey = $this->primaryKey($table);

        if ($primaryKey !== null) {
            $query->orderBy($primaryKey);
        }

        return $query->get()
            ->map(fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }

    private function primaryKey(string $table): ?string
    {
        $safeTable = str_replace('`', '``', $table);
        $index = collect(DB::select("SHOW KEYS FROM `{$safeTable}` WHERE Key_name = 'PRIMARY'"))->first();

        return $index?->Column_name;
    }
}
