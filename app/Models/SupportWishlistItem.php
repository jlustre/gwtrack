<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportWishlistItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'title',
        'module',
        'problem_solved',
        'suggested_description',
        'example_link',
        'business_value',
        'user_priority',
        'admin_priority_score',
        'development_complexity',
        'estimated_effort_hours',
        'target_release_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'business_value' => 'array',
            'admin_priority_score' => 'integer',
            'estimated_effort_hours' => 'integer',
            'target_release_date' => 'date',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SupportWishlistVote::class, 'wishlist_item_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(SupportTicketAttachment::class, 'attachable');
    }
}
