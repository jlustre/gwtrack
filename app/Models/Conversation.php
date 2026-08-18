<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ConversationMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ConversationTag::class);
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    public function displayNameFor(User $viewer): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->isDirect()) {
            $other = $this->activeMembers
                ->first(fn (ConversationMember $member): bool => (int) $member->user_id !== (int) $viewer->id)
                ?->user;

            return $other?->name ?? 'Direct Message';
        }

        return 'Conversation';
    }
}
