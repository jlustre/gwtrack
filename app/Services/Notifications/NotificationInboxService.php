<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\NotificationPreferenceDefault;
use App\Models\NotificationType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationInboxService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function inbox(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->baseQuery($user), $filters)
            ->with(['notificationType', 'trigger', 'senderUser'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function recent(User $user, int $limit = 8): Collection
    {
        return $this->applyFilters($this->baseQuery($user), ['unread' => null])
            ->with(['notificationType'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return $this->applyFilters($this->baseQuery($user), ['unread' => true])->count();
    }

    /**
     * @return array{unread: int, read: int, archived: int, total: int}
     */
    public function stats(User $user): array
    {
        $base = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);

        return [
            'unread' => (clone $base)
                ->whereNull('archived_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now());
                })
                ->whereNull('read_at')
                ->count(),
            'read' => (clone $base)->whereNull('archived_at')->whereNotNull('read_at')->count(),
            'archived' => (clone $base)->whereNotNull('archived_at')->count(),
            'total' => (clone $base)->whereNull('archived_at')->count(),
        ];
    }

    public function markAsRead(User $user, string $notificationId): Notification
    {
        return $this->notifications->markAsRead($user, $notificationId);
    }

    public function markAsUnread(User $user, string $notificationId): Notification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['read_at' => null]);

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notifications->markAllAsRead($user);
    }

    public function archive(User $user, string $notificationId): Notification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['archived_at' => now()]);

        return $notification->fresh();
    }

    public function unarchive(User $user, string $notificationId): Notification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['archived_at' => null]);

        return $notification->fresh();
    }

    public function snooze(User $user, string $notificationId, \DateTimeInterface $until): Notification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['snoozed_until' => $until]);

        return $notification->fresh();
    }

    public function clearSnooze(User $user, string $notificationId): Notification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['snoozed_until' => null]);

        return $notification->fresh();
    }

    public function delete(User $user, string $notificationId): void
    {
        $this->findForUser($user, $notificationId)->delete();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function criticalAlerts(User $user, int $limit = 3): Collection
    {
        $priorities = config('notifications.critical_priorities', ['urgent', 'critical']);

        return $this->applyFilters($this->baseQuery($user), ['unread' => true])
            ->whereIn('priority', $priorities)
            ->with(['notificationType'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function baseQuery(User $user): Builder
    {
        return Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $showArchived = (bool) ($filters['archived'] ?? false);

        if ($showArchived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at')
                ->where(function (Builder $inner): void {
                    $inner->whereNull('snoozed_until')
                        ->orWhere('snoozed_until', '<=', now());
                });
        }

        if (array_key_exists('unread', $filters) && $filters['unread'] !== null) {
            $filters['unread']
                ? $query->whereNull('read_at')
                : $query->whereNotNull('read_at');
        }

        if (! empty($filters['tab']) && $filters['tab'] !== 'all') {
            $tab = config("notifications.center_tabs.{$filters['tab']}");

            if ($tab) {
                if (! empty($tab['unread'])) {
                    $query->whereNull('read_at');
                }

                if (! empty($tab['type_codes'])) {
                    $typeIds = NotificationType::query()
                        ->whereIn('code', $tab['type_codes'])
                        ->pluck('id');

                    $query->whereIn('notification_type_id', $typeIds);
                }
            }
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['search'])) {
            $needle = '%'.strtolower(trim($filters['search'])).'%';
            $query->where(function (Builder $inner) use ($needle): void {
                $inner->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.title"))) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.message"))) LIKE ?', [$needle]);
            });
        }

        if (! empty($filters['type_code'])) {
            $typeId = NotificationType::query()->where('code', $filters['type_code'])->value('id');

            $typeId
                ? $query->where('notification_type_id', $typeId)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected function findForUser(User $user, string $notificationId): Notification
    {
        return $this->baseQuery($user)->whereKey($notificationId)->firstOrFail();
    }
}
