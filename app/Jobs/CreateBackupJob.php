<?php

namespace App\Jobs;

use App\Events\BackupCompleted;
use App\Events\BackupFailed;
use App\Events\BackupStarted;
use App\Models\Backup;
use App\Notifications\BackupCompletedNotification;
use App\Notifications\BackupFailedNotification;
use App\Services\Backups\BackupAuditService;
use App\Services\Backups\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(public Backup $backup)
    {
    }

    public function handle(BackupService $backupService, BackupAuditService $audit): void
    {
        $this->backup->refresh();

        try {
            event(new BackupStarted($this->backup));
            $audit->record('backup.started', $this->backup, ['tables' => $this->backup->included_tables], $this->backup->created_by);

            $backup = $backupService->create($this->backup);

            event(new BackupCompleted($backup));
            $audit->record('backup.completed', $backup, ['file_size' => $backup->file_size], $backup->created_by);
            $backup->creator?->notify(new BackupCompletedNotification($backup));
        } catch (Throwable $exception) {
            $this->backup->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            event(new BackupFailed($this->backup));
            $audit->record('backup.failed', $this->backup, ['error' => $exception->getMessage()], $this->backup->created_by);
            $this->backup->creator?->notify(new BackupFailedNotification($this->backup));

            throw $exception;
        }
    }
}
