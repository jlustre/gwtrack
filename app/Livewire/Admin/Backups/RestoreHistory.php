<?php

namespace App\Livewire\Admin\Backups;

use App\Models\RestoreLog;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class RestoreHistory extends Component
{
    use WithPagination;

    public string $status = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') && auth()->user()?->can('view backups'), 403);
    }

    public function render(): View
    {
        $query = RestoreLog::with(['restorer', 'backup', 'items'])->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.backups.restore-history', [
            'restores' => $query->paginate(15),
        ])->layout('layouts.app');
    }
}
