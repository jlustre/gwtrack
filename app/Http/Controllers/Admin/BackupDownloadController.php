<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(Backup $backup): StreamedResponse
    {
        abort_unless(auth()->user()?->can('download', $backup), 403);
        abort_unless($backup->status === 'completed' && $backup->file_path, 404);

        return Storage::disk($backup->disk ?: config('backup-classification.disk', 'backups'))
            ->download($backup->file_path, $backup->filename);
    }
}
