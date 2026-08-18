<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationTrigger;
use App\Services\Notifications\NotificationAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminNotificationDashboard extends Component
{
    public ?int $testTriggerId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view notification logs'), 403);
    }

    public function sendTestNotification(NotificationAdminService $admin): void
    {
        abort_unless(auth()->user()->can('manage notification templates'), 403);

        if (! $this->testTriggerId) {
            $this->addError('testTriggerId', 'Select a trigger to send a test notification.');

            return;
        }

        if (! $admin->sendTemplateTest($this->testTriggerId, auth()->user())) {
            $this->addError('testTriggerId', 'Unable to send test notification for that trigger.');

            return;
        }

        session()->flash('notification_admin_status', 'Test notification queued for your account.');
    }

    public function render(NotificationAdminService $admin): View
    {
        return view('livewire.admin.notifications.admin-notification-dashboard', [
            'metrics' => $admin->dashboardMetrics(),
            'triggers' => NotificationTrigger::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }
}
