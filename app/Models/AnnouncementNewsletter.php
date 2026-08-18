<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementNewsletter extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'period_type',
        'period_starts_at',
        'period_ends_at',
        'status',
        'subject',
        'html_body',
        'text_body',
        'compiled_sections',
        'announcement_ids',
        'created_by',
        'sent_at',
        'sent_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'compiled_sections' => 'array',
            'announcement_ids' => 'array',
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'sent_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
