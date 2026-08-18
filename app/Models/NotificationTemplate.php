<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'notification_trigger_id',
        'name',
        'subject',
        'body',
        'in_app_title',
        'in_app_message',
        'sms_body',
        'push_title',
        'push_body',
        'action_label',
        'action_url_template',
        'channels',
        'placeholders',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'notification_trigger_id' => 'integer',
            'channels' => 'array',
            'placeholders' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(NotificationTrigger::class, 'notification_trigger_id');
    }

    public function snapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'channels' => $this->channels,
            'placeholders' => $this->placeholders,
        ];
    }

    public function renderSubject(array $tokens): string
    {
        return $this->render($this->subject, $tokens);
    }

    public function renderBody(array $tokens): string
    {
        return $this->render($this->body, $tokens);
    }

    public function renderSmsBody(array $tokens): string
    {
        return $this->render((string) ($this->sms_body ?? ''), $tokens);
    }

    public function renderPushTitle(array $tokens): string
    {
        return $this->render((string) ($this->push_title ?? $this->subject), $tokens);
    }

    public function renderPushBody(array $tokens): string
    {
        return $this->render((string) ($this->push_body ?? $this->in_app_message ?? ''), $tokens);
    }

    private function render(string $content, array $tokens): string
    {
        foreach ($tokens as $token => $value) {
            $content = str_replace('{{ '.$token.' }}', $value, $content);
            $content = str_replace('{{'.$token.'}}', $value, $content);
        }

        return $content;
    }
}
