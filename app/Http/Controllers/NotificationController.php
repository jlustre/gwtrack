<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $typeCounts = $user->notifications()
            ->get()
            ->groupBy(fn ($notification) => data_get($notification->data, 'category', 'General'))
            ->map->count()
            ->sortDesc();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
            'readCount' => $user->readNotifications()->count(),
            'typeCounts' => $typeCounts,
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back()->with('status', 'notification-read');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'notifications-read');
    }
}
