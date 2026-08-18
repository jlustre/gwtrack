<?php

namespace App\Notifications;

use App\Models\RestoreLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RestoreFailedNotification extends Notification
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
            'title' => 'Restore failed',
            'message' => $this->restoreLog->error_message ?: 'The restore could not be completed.',
            'restore_log_id' => $this->restoreLog->id,
            'status' => $this->restoreLog->status,
            'action_url' => route('admin.backups.restores'),
        ];
    }
}
