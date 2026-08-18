<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backups\BackupStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldBackupsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(BackupStorageService $storage): void
    {
        $cutoff = now()->subDays((int) config('backup-classification.retention_days', 30));

        Backup::query()
            ->where('created_at', '<', $cutoff)
            ->where('backup_type', '!=', 'pre_restore')
            ->chunkById(100, function ($backups) use ($storage): void {
                foreach ($backups as $backup) {
                    $storage->deleteBackupFile($backup);
                    $backup->delete();
                }
            });
    }
}
