<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalNote extends Model
{
    protected $fillable = [
        'goal_id',
        'author_id',
        'note_type',
        'body',
        'audio_path',
        'audio_duration_seconds',
        'is_private',
    ];

    public function hasAudio(): bool
    {
        return filled($this->audio_path);
    }

    public function audioUrl(): ?string
    {
        return $this->audio_path ? asset('storage/'.$this->audio_path) : null;
    }

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
