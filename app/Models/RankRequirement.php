<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RankRequirement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rank_id',
        'title',
        'description',
        'category',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserRankProgress::class);
    }

    public function categoryLabel(): string
    {
        return config('rank-advancement.categories.'.$this->category, ucfirst(str_replace('_', ' ', $this->category ?? 'general')));
    }
}
