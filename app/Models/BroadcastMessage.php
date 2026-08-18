<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BroadcastMessage extends Model
{
    use SoftDeletes;

    protected $table = 'broadcast_messages';

    protected $fillable = [
        'title',
        'body',
        'sender_id',
        'audience_type',
        'audience_config',
        'status',
        'priority',
        'sent_at',
        'recipient_count',
    ];

    protected function casts(): array
    {
        return [
            'audience_config' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
