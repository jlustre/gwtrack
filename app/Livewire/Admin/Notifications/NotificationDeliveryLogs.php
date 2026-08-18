<?php

namespace App\Livewire\Admin\Notifications;

use App\Services\Notifications\NotificationAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationDeliveryLogs extends Component
{
    use WithPagination;

    public string $status = '';

    public string $channel = '';

    public string $search = '';

    protected $queryString = [
        'status' => ['except' => ''],
        'channel' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view notification logs'), 403);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedChannel(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resend(int $logId, NotificationAdminService $admin): void
    {
        abort_unless(auth()->user()->can('manage notification settings'), 403);

        if ($admin->resendDeliveryLog($logId)) {
            session()->flash('notification_admin_status', 'Failed delivery queued for retry.');

            return;
        }

        session()->flash('notification_admin_status', 'Unable to resend that delivery log.');
    }

    public function render(NotificationAdminService $admin): View
    {
        return view('livewire.admin.notifications.notification-delivery-logs', [
            'logs' => $admin->deliveryLogs(
                status: $this->status ?: null,
                channel: $this->channel ?: null,
                search: $this->search ?: null,
            ),
        ]);
    }
}
