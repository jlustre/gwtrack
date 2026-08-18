<?php

namespace App\Support;

use App\Models\Notification;

class NotificationPresentation
{
    public static function categoryTone(?string $category): string
    {
        return match (strtolower((string) $category)) {
            'mentorship', 'mentor assignment', 'fap', 'goals & performance' => 'bg-gw-accent',
            'training' => 'bg-emerald-500',
            'licensing', 'compliance', 'fna management' => 'bg-amber-600',
            'support' => 'bg-red-500',
            'event', 'events', 'calendar', 'booking' => 'bg-sky-500',
            'rank advancement' => 'bg-purple-500',
            'announcement', 'announcements', 'system' => 'bg-orange-500',
            default => 'bg-gw-primary',
        };
    }

    public static function priorityBadgeClasses(?string $priority): string
    {
        return match ($priority) {
            'critical' => 'bg-red-100 text-red-800 border-red-200',
            'urgent' => 'bg-orange-100 text-orange-800 border-orange-200',
            'high' => 'bg-amber-100 text-amber-900 border-amber-200',
            'medium' => 'bg-gw-accent-soft text-gw-accent-dark border-gw-accent/30',
            'low' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-50 text-slate-500 border-slate-100',
        };
    }

    public static function priorityLabel(?string $priority): string
    {
        return config("notifications.priorities.{$priority}.label", ucfirst($priority ?? 'info'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function summarize(Notification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => data_get($data, 'title', 'Notification'),
            'message' => data_get($data, 'message', ''),
            'category' => data_get($data, 'category', 'General'),
            'priority' => $notification->priority ?? data_get($data, 'priority', 'info'),
            'is_read' => $notification->read_at !== null,
            'is_archived' => $notification->archived_at !== null,
            'snoozed_until' => $notification->snoozed_until,
            'action_url' => NotificationActionUrl::fromNotificationData($data),
            'created_at' => $notification->created_at,
            'created_human' => $notification->created_at?->diffForHumans(),
            'tone' => self::categoryTone(data_get($data, 'category')),
        ];
    }
}
