<?php

namespace App\Services\Backups;

use App\Models\RestoreLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

final class RestoreService
{
    public function restore(RestoreLog $restoreLog, ValidatedBackupArchive $archive): void
    {
        $tables = $restoreLog->restored_tables ?? [];

        $restoreLog->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                $this->restoreTable($restoreLog, $archive, $table);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Artisan::call('cache:clear');

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $restoreLog->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    private function restoreTable(RestoreLog $restoreLog, ValidatedBackupArchive $archive, string $table): void
    {
        $payload = $archive->tablePayload($table);
        $records = $payload['records'] ?? [];
        $primaryKey = $payload['primary_key'] ?? null;
        $recordsBefore = DB::table($table)->count();
        $restored = 0;
        $skipped = 0;

        DB::transaction(function () use ($restoreLog, $table, $records, $primaryKey, &$restored, &$skipped): void {
            if ($restoreLog->restore_mode === 'replace') {
                DB::table($table)->delete();
            }

            $columns = Schema::getColumnListing($table);

            foreach ($records as $record) {
                $record = array_intersect_key((array) $record, array_flip($columns));
                $result = $this->restoreRecord($table, $record, is_string($primaryKey) ? $primaryKey : null, $restoreLog->restore_mode);
                $restored += $result === 'restored' ? 1 : 0;
                $skipped += $result === 'skipped' ? 1 : 0;
            }
        });

        $restoreLog->items()->create([
            'table_name' => $table,
            'records_before' => $recordsBefore,
            'records_restored' => $restored,
            'records_skipped' => $skipped,
            'status' => 'completed',
            'metadata' => [
                'restore_mode' => $restoreLog->restore_mode,
                'primary_key' => $primaryKey,
            ],
        ]);
    }

    private function restoreRecord(string $table, array $record, ?string $primaryKey, string $mode): string
    {
        if ($record === []) {
            return 'skipped';
        }

        if ($primaryKey && array_key_exists($primaryKey, $record)) {
            if ($mode === 'merge') {
                DB::table($table)->updateOrInsert([$primaryKey => $record[$primaryKey]], $record);

                return 'restored';
            }

            if ($mode === 'skip_duplicates' && DB::table($table)->where($primaryKey, $record[$primaryKey])->exists()) {
                return 'skipped';
            }
        }

        DB::table($table)->insert($record);

        return 'restored';
    }
}
