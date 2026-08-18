<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BackupCompletedNotification extends Notification
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
            'title' => 'Backup completed',
            'message' => $this->backup->filename.' is ready to download.',
            'backup_id' => $this->backup->id,
            'status' => $this->backup->status,
            'action_url' => route('admin.backups.history'),
        ];
    }
}
