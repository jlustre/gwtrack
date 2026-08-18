<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'type',
        'module',
        'category',
        'user_intent_action',
        'user_reported_outcome',
        'subject',
        'description',
        'urgency',
        'impact',
        'frequency',
        'device',
        'browser',
        'related_url',
        'status_id',
        'priority_score',
        'sla_status',
        'resolved_at',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'priority_score' => 'integer',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SupportTicketStatus::class, 'status_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SupportTicketComment::class, 'ticket_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(SupportTicketInternalNote::class, 'ticket_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(SupportTicketAttachment::class, 'attachable');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SupportTicketStatusHistory::class, 'ticket_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SupportTicketAssignment::class, 'ticket_id');
    }

    public function wishlistItem(): HasOne
    {
        return $this->hasOne(SupportWishlistItem::class, 'ticket_id');
    }

    public function isClosed(): bool
    {
        $slug = $this->status?->slug;

        return $slug !== null && in_array($slug, config('support.closed_status_slugs', []), true);
    }
}
