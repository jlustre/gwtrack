<?php

namespace App\Livewire\Admin\Backups;

use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Services\Backups\BackupClassificationService;
use App\Services\Backups\BackupStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateBackup extends Component
{
    public string $backupType = 'structural';
    public array $availableTables = [];
    public array $selectedTables = [];
    public array $excludedTables = [];
    public bool $compressed = true;
    public bool $encrypted = false;
    public ?string $notes = null;

    public function mount(BackupClassificationService $classification): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') && auth()->user()?->can('create backups'), 403);

        $this->syncTables($classification);
    }

    public function updatedBackupType(BackupClassificationService $classification): void
    {
        $this->syncTables($classification);
    }

    public function createBackup(BackupClassificationService $classification, BackupStorageService $storage): void
    {
        $this->validate([
            'backupType' => ['required', Rule::in(['structural', 'transactional', 'combined', 'full'])],
            'selectedTables' => ['required', 'array', 'min:1'],
            'excludedTables' => ['array'],
            'compressed' => ['boolean'],
            'encrypted' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $selected = $classification->validateTables($this->selectedTables);
        $excluded = $classification->validateTables($this->excludedTables);
        $tables = array_values(array_diff($selected, $excluded));

        if ($tables === []) {
            $this->addError('selectedTables', 'Select at least one approved table.');

            return;
        }

        $backup = Backup::create([
            'backup_type' => $this->backupType,
            'filename' => $storage->makeFilename($this->backupType),
            'status' => 'pending',
            'included_tables' => $tables,
            'excluded_tables' => $excluded,
            'created_by' => auth()->id(),
            'compressed' => $this->compressed,
            'encrypted' => $this->encrypted,
            'notes' => $this->notes,
            'disk' => $storage->diskName(),
        ]);

        CreateBackupJob::dispatch($backup);

        $this->notes = null;
        session()->flash('backup_status', 'Backup queued. The history page will update when the worker completes it.');
    }

    public function render(): View
    {
        return view('livewire.admin.backups.create-backup')->layout('layouts.app');
    }

    private function syncTables(BackupClassificationService $classification): void
    {
        $this->availableTables = $classification->tablesFor($this->backupType);
        $this->selectedTables = $this->availableTables;
        $this->excludedTables = [];
    }
}
