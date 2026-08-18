<?php

namespace App\Livewire\Notifications;

use App\Services\Notifications\NotificationInboxService;
use App\Support\NotificationPresentation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notifications')]
class NotificationCenter extends Component
{
    use WithPagination;

    #[Url(as: 'tab', except: 'all')]
    public string $activeTab = 'all';

    #[Url(as: 'archived', except: false)]
    public bool $showArchived = false;

    public string $search = '';

    public ?string $priorityFilter = null;

    public ?string $snoozeNotificationId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view notifications'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->resetPage();
    }

    public function markRead(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->markAsRead(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function markUnread(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->markAsUnread(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function markAllRead(NotificationInboxService $inbox): void
    {
        $inbox->markAllAsRead(auth()->user());
        $this->dispatch('notifications-updated');
    }

    public function archive(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->archive(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function unarchive(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->unarchive(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function snooze(string $notificationId, string $option, NotificationInboxService $inbox): void
    {
        $config = config("notifications.snooze_options.{$option}");

        if (! $config) {
            return;
        }

        $until = $option === 'tomorrow'
            ? now()->addDay()->startOfDay()->addHours(8)
            : now()->addHours($config['hours'] ?? 1);

        $inbox->snooze(auth()->user(), $notificationId, $until);
        $this->snoozeNotificationId = null;
        $this->dispatch('notifications-updated');
    }

    public function delete(string $notificationId, NotificationInboxService $inbox): void
    {
        $inbox->delete(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');
    }

    public function openNotification(string $notificationId, NotificationInboxService $inbox): void
    {
        $notification = $inbox->markAsRead(auth()->user(), $notificationId);
        $this->dispatch('notifications-updated');

        $url = \App\Support\NotificationActionUrl::fromNotificationData($notification->data ?? []);

        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    #[On('notifications-updated')]
    public function refreshList(): void
    {
        // Re-render
    }

    public function render(NotificationInboxService $inbox): View
    {
        $user = auth()->user();
        $filters = [
            'tab' => $this->activeTab,
            'archived' => $this->showArchived,
            'search' => $this->search,
            'priority' => $this->priorityFilter,
            'per_page' => 12,
        ];

        $notifications = $inbox->inbox($user, $filters);
        $stats = $inbox->stats($user);

        return view('livewire.notifications.notification-center', [
            'notifications' => $notifications,
            'stats' => $stats,
            'tabs' => config('notifications.center_tabs', []),
            'priorities' => config('notifications.priorities', []),
            'snoozeOptions' => config('notifications.snooze_options', []),
            'summarize' => fn ($n) => NotificationPresentation::summarize($n),
        ])->layout('layouts.app');
    }
}
