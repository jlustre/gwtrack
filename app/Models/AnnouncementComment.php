<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnouncementComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'announcement_id',
        'parent_id',
        'user_id',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'announcement_id' => 'integer',
            'parent_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(MessageCenterAnnouncement::class, 'announcement_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }
}
