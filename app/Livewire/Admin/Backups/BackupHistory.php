<?php

namespace App\Livewire\Admin\Backups;

use App\Models\Backup;
use App\Services\Backups\BackupStorageService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class BackupHistory extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $status = '';
    public ?int $metadataBackupId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') && auth()->user()?->can('view backups'), 403);
    }

    public function deleteBackup(Backup $backup, BackupStorageService $storage): void
    {
        abort_unless(auth()->user()?->can('delete backups'), 403);

        $storage->deleteBackupFile($backup);
        $backup->delete();

        session()->flash('backup_status', 'Backup deleted.');
    }

    public function showMetadata(int $backupId): void
    {
        $this->metadataBackupId = $backupId;
    }

    public function render(): View
    {
        $query = Backup::with('creator')->latest();

        if ($this->search !== '') {
            $query->where(function ($query): void {
                $query->where('filename', 'like', '%'.$this->search.'%')
                    ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        if ($this->type !== '') {
            $query->where('backup_type', $this->type);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.backups.backup-history', [
            'backups' => $query->paginate(15),
            'metadataBackup' => $this->metadataBackupId ? Backup::find($this->metadataBackupId) : null,
        ])->layout('layouts.app');
    }
}
