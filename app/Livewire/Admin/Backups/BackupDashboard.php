<?php

namespace App\Livewire\Admin\Backups;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\RestoreLog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BackupDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin')
            && auth()->user()?->hasAnyPermission(['view backups', 'manage backups', 'manage backup schedules']),
            403
        );
    }

    public function render(): View
    {
        return view('livewire.admin.backups.backup-dashboard', [
            'metrics' => [
                'total_backups' => Backup::count(),
                'last_successful_backup' => Backup::where('status', 'completed')->latest('completed_at')->first(),
                'failed_backups' => Backup::where('status', 'failed')->count(),
                'storage_used' => Backup::where('status', 'completed')->sum('file_size'),
                'next_scheduled_backup' => BackupSchedule::where('is_active', true)->orderBy('next_run_at')->first(),
            ],
            'recentBackups' => Backup::with('creator')->latest()->limit(8)->get(),
            'recentRestores' => RestoreLog::with('restorer')->latest()->limit(8)->get(),
        ])->layout('layouts.app');
    }
}
