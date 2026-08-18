<?php

namespace App\Livewire\Notifications;

use App\Services\Notifications\NotificationInboxService;
use App\Support\NotificationActionUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class CriticalAlertBanner extends Component
{
    /** @var list<string> */
    public array $dismissedIds = [];

    protected $listeners = [
        'notifications-updated' => '$refresh',
    ];

    public function dismiss(string $notificationId): void
    {
        $this->dismissedIds[] = $notificationId;
    }

    public function markRead(string $notificationId, NotificationInboxService $inbox): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $inbox->markAsRead($user, $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function render(NotificationInboxService $inbox): View
    {
        $user = Auth::user();
        $alerts = collect();

        if ($user) {
            $alerts = $inbox->criticalAlerts($user, 5)
                ->reject(fn ($notification) => in_array($notification->id, $this->dismissedIds, true))
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Critical alert',
                    'message' => Str::limit($notification->data['message'] ?? '', 140),
                    'priority' => $notification->priority ?? 'critical',
                    'priority_label' => config('notifications.priorities.'.($notification->priority ?? 'critical').'.label', 'Critical'),
                    'action_url' => NotificationActionUrl::fromNotificationData($notification->data ?? []),
                ])
                ->values();
        }

        return view('livewire.notifications.critical-alert-banner', [
            'alerts' => $alerts,
        ]);
    }
}
