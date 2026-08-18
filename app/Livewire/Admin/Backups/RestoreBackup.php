<?php

namespace App\Livewire\Admin\Backups;

use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\RestoreLog;
use App\Services\Backups\BackupValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class RestoreBackup extends Component
{
    use WithFileUploads;

    public $backupFile;
    public ?int $backupId = null;
    public ?string $storedUploadPath = null;
    public array $preview = [];
    public array $selectedTables = [];
    public string $restoreType = 'selected';
    public string $restoreMode = 'skip_duplicates';
    public string $password = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') && auth()->user()?->can('restore backups'), 403);

        $backupId = request()->integer('backup');

        if ($backupId) {
            $backup = Backup::findOrFail($backupId);
            $this->backupId = $backup->id;
            $this->preview = [
                'filename' => $backup->filename,
                'backup_type' => $backup->backup_type,
                'created_at' => optional($backup->created_at)->toDateTimeString(),
                'included_tables' => $backup->included_tables ?? [],
                'record_counts' => $backup->metadata['record_counts'] ?? [],
            ];
            $this->selectedTables = $backup->included_tables ?? [];
        }
    }

    public function validateBackup(BackupValidationService $validation): void
    {
        $this->validate([
            'backupFile' => ['required', 'file', 'mimes:zip', 'max:'.((int) config('backup-classification.max_upload_mb', 500) * 1024)],
        ]);

        $archive = $validation->validatePath($this->backupFile->getRealPath());

        try {
            $this->preview = [
                'filename' => $this->backupFile->getClientOriginalName(),
                'backup_type' => $archive->metadata['backup_type'] ?? 'unknown',
                'created_at' => $archive->metadata['created_at'] ?? null,
                'included_tables' => $archive->tables,
                'record_counts' => $archive->recordCounts,
                'database_name' => $archive->metadata['database_name'] ?? null,
            ];
            $this->selectedTables = $archive->tables;
            $this->storedUploadPath = $this->backupFile->storeAs(
                'uploads',
                Str::uuid().'-'.preg_replace('/[^A-Za-z0-9_.-]/', '-', $this->backupFile->getClientOriginalName()),
                config('backup-classification.disk', 'backups')
            );
            session()->flash('restore_status', 'Backup validated. Review the table list before restoring.');
        } finally {
            $archive->cleanup();
        }
    }

    public function restore(BackupValidationService $validation): void
    {
        $this->validate([
            'restoreType' => ['required', Rule::in(['structural', 'transactional', 'combined', 'selected'])],
            'restoreMode' => ['required', Rule::in(['replace', 'merge', 'skip_duplicates'])],
            'selectedTables' => ['required', 'array', 'min:1'],
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'The password confirmation did not match your account.');

            return;
        }

        $sourcePath = $this->storedUploadPath;
        $backupId = $this->backupId;
        $sourceFilename = $this->preview['filename'] ?? null;

        if ($backupId) {
            $backup = Backup::findOrFail($backupId);
            $sourcePath = $backup->file_path;
            $sourceFilename = $backup->filename;
        }

        if (! $sourcePath) {
            $this->addError('backupFile', 'Validate a backup file before restoring.');

            return;
        }

        $archive = $validation->validateStored($sourcePath, config('backup-classification.disk', 'backups'));

        try {
            $allowedTables = $archive->tables;
            $tables = array_values(array_intersect($this->selectedTables, $allowedTables));
        } finally {
            $archive->cleanup();
        }

        if ($tables === []) {
            $this->addError('selectedTables', 'Select at least one table from the validated backup.');

            return;
        }

        $restoreLog = RestoreLog::create([
            'backup_id' => $backupId,
            'restore_type' => $this->restoreType,
            'restore_mode' => $this->restoreMode,
            'status' => 'pending',
            'restored_by' => auth()->id(),
            'restored_tables' => $tables,
            'metadata' => ['preview' => $this->preview],
            'source_filename' => $sourceFilename,
            'source_file_path' => $sourcePath,
        ]);

        RestoreBackupJob::dispatch($restoreLog);

        $this->password = '';
        session()->flash('restore_status', 'Restore queued. A pre-restore backup will be created before data changes.');
    }

    public function render(): View
    {
        return view('livewire.admin.backups.restore-backup')->layout('layouts.app');
    }
}
