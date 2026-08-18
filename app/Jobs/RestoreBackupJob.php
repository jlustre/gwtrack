<?php

namespace App\Jobs;

use App\Events\RestoreCompleted;
use App\Events\RestoreFailed;
use App\Events\RestoreStarted;
use App\Models\Backup;
use App\Models\RestoreLog;
use App\Notifications\RestoreCompletedNotification;
use App\Notifications\RestoreFailedNotification;
use App\Services\Backups\BackupAuditService;
use App\Services\Backups\BackupService;
use App\Services\Backups\BackupStorageService;
use App\Services\Backups\BackupValidationService;
use App\Services\Backups\RestoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(public RestoreLog $restoreLog)
    {
    }

    public function handle(
        BackupService $backupService,
        BackupStorageService $storage,
        BackupValidationService $validation,
        RestoreService $restoreService,
        BackupAuditService $audit,
    ): void {
        $this->restoreLog->refresh();

        try {
            event(new RestoreStarted($this->restoreLog));
            $audit->record('restore.started', $this->restoreLog, ['tables' => $this->restoreLog->restored_tables], $this->restoreLog->restored_by);

            $preRestoreBackup = Backup::create([
                'backup_type' => 'pre_restore',
                'filename' => $storage->makeFilename('pre-restore-'.$this->restoreLog->id),
                'status' => 'pending',
                'included_tables' => $this->restoreLog->restored_tables,
                'excluded_tables' => [],
                'created_by' => $this->restoreLog->restored_by,
                'compressed' => true,
                'encrypted' => false,
                'notes' => 'Automatic pre-restore backup for restore #'.$this->restoreLog->id,
                'disk' => $storage->diskName(),
            ]);

            $backupService->create($preRestoreBackup);
            $this->restoreLog->update(['pre_restore_backup_id' => $preRestoreBackup->id]);

            $archive = $validation->validateStored($this->restoreLog->source_file_path, config('backup-classification.disk', 'backups'));

            try {
                $restoreService->restore($this->restoreLog, $archive);
            } finally {
                $archive->cleanup();
            }

            $this->restoreLog->refresh();
            event(new RestoreCompleted($this->restoreLog));
            $audit->record('restore.completed', $this->restoreLog, ['pre_restore_backup_id' => $preRestoreBackup->id], $this->restoreLog->restored_by);
            $this->restoreLog->restorer?->notify(new RestoreCompletedNotification($this->restoreLog));
        } catch (Throwable $exception) {
            $this->restoreLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            event(new RestoreFailed($this->restoreLog));
            $audit->record('restore.failed', $this->restoreLog, ['error' => $exception->getMessage()], $this->restoreLog->restored_by);
            $this->restoreLog->restorer?->notify(new RestoreFailedNotification($this->restoreLog));

            throw $exception;
        }
    }
}
