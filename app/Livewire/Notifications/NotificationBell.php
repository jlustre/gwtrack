<?php

namespace App\Livewire\Notifications;

use App\Services\Notifications\NotificationInboxService;
use App\Support\NotificationActionUrl;
use App\Support\NotificationPresentation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array<string, mixed>> */
    public array $toasts = [];

    public ?string $lastSeenNotificationId = null;

    public function mount(NotificationInboxService $inbox): void
    {
        abort_unless(auth()->user()?->can('view notifications'), 403);

        $this->refreshFeed($inbox);
    }

    #[On('notifications-updated')]
    public function refreshFeed(NotificationInboxService $inbox): void
    {
        $user = auth()->user();
        $previousLatestId = $this->items[0]['id'] ?? $this->lastSeenNotificationId;

        $this->unreadCount = $inbox->unreadCount($user);
        $this->items = $inbox->recent($user, 8)
            ->map(fn ($notification) => NotificationPresentation::summarize($notification))
            ->all();

        if ($this->items !== []) {
            $latest = $this->items[0];

            if (
                $previousLatestId
                && $latest['id'] !== $previousLatestId
                && ! $latest['is_read']
                && in_array($latest['priority'], config('notifications.toast_priorities', []), true)
            ) {
                $this->pushToast($latest['title'], $latest['message'], $latest['priority']);
            }

            $this->lastSeenNotificationId = $latest['id'];
        }
    }

    public function markRead(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->markAsRead(auth()->user(), $notificationId);
        $this->refreshFeed($inbox);
        $this->dispatch('notifications-updated');
    }

    public function markAllRead(NotificationInboxService $inbox): void
    {
        $inbox->markAllAsRead(auth()->user());
        $this->refreshFeed($inbox);
        $this->dispatch('notifications-updated');
    }

    public function dismissToast(int $index): void
    {
        unset($this->toasts[$index]);
        $this->toasts = array_values($this->toasts);
    }

    public function openRelated(string $notificationId, NotificationInboxService $inbox): void
    {
        $notification = $inbox->markAsRead(auth()->user(), $notificationId);
        $url = NotificationActionUrl::fromNotificationData($notification->data ?? []);

        $this->refreshFeed($inbox);
        $this->dispatch('notifications-updated');

        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-bell');
    }

    /**
     * @param  array<string, mixed>  $latest
     */
    private function pushToast(string $title, string $message, string $priority): void
    {
        $this->toasts[] = [
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
        ];

        if (count($this->toasts) > 3) {
            $this->toasts = array_slice($this->toasts, -3);
        }
    }
}
