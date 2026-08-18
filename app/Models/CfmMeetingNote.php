<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmMeetingNote extends Model
{
    protected $fillable = [
        'cfm_meeting_id',
        'summary',
        'action_items',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'action_items' => 'array',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(CfmMeeting::class, 'cfm_meeting_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
