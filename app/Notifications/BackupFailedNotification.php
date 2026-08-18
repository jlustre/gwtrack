<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BackupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Backup $backup)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Backup failed',
            'message' => $this->backup->error_message ?: 'The backup could not be completed.',
            'backup_id' => $this->backup->id,
            'status' => $this->backup->status,
            'action_url' => route('admin.backups.history'),
        ];
    }
}
