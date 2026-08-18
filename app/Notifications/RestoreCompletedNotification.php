<?php

namespace App\Notifications;

use App\Models\RestoreLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RestoreCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RestoreLog $restoreLog)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Restore completed',
            'message' => 'Restore #'.$this->restoreLog->id.' completed successfully.',
            'restore_log_id' => $this->restoreLog->id,
            'status' => $this->restoreLog->status,
            'action_url' => route('admin.backups.restores'),
        ];
    }
}
