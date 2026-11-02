<?php

namespace App\Services\Backups;

use App\Models\Backup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class BackupStorageService
{
    public function diskName(): string
    {
        return (string) config('backup-classification.disk', 'backups');
    }

    public function makeFilename(string $type): string
    {
        return sprintf('gwtracks-%s-%s.zip', Str::slug($type), now()->format('Ymd-His'));
    }

    public function storeWorkingDirectory(Backup $backup, string $workingPath): Backup
    {
        $zipPath = storage_path("app/backups/work/{$backup->id}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create backup ZIP archive.');
        }

        foreach (File::allFiles($workingPath) as $file) {
            $zip->addFile($file->getRealPath(), str_replace('\\', '/', $file->getRelativePathname()));
        }

        $zip->close();

        $relativePath = 'archives/'.$backup->filename;
        Storage::disk($this->diskName())->put($relativePath, File::get($zipPath));

        $backup->update([
            'file_path' => $relativePath,
            'file_size' => Storage::disk($this->diskName())->size($relativePath),
            'disk' => $this->diskName(),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        File::delete($zipPath);
        File::deleteDirectory($workingPath);

        return $backup->refresh();
    }

    public function absolutePath(string $relativePath, ?string $disk = null): string
    {
        $diskName = $disk ?: $this->diskName();

        if (! method_exists(Storage::disk($diskName), 'path')) {
            throw new RuntimeException('The configured backup disk must support local file paths for restore operations.');
        }

        return Storage::disk($diskName)->path($relativePath);
    }

    public function deleteBackupFile(Backup $backup): void
    {
        if ($backup->file_path) {
            Storage::disk($backup->disk ?: $this->diskName())->delete($backup->file_path);
        }
    }
}
